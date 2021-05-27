<?php

namespace Maba\GentleForce\Tests\Functional;

/**
 * A simple interfacing for forking and returning information from N workers.
 */
class Forker
{
    /**
     * Forks the process count($things) times and executes the callback on each
     * entry in $things, all in parallel.  Collects the return values from each
     * callback and returns them.
     *
     * $things:   An array of anything you want, one process will be created for
     *            each entry.
     *
     * $callback: Function to call for each worker. This function should do the
     *            body of the work, return the results, and has a signature like
     *            this: function ($key, $value) {}
     *
     * returns:   An array with the same keys as $things but with the results of
     *            each callback as the value.
     *
     * Example:
     *    $results = Forker::map($things, function ($index, $thing) {
     *       // Some expensive operation
     *       return calculateNewThing($thing);
     *    });
     * @param mixed $things
     * @param mixed $callback
     */
    public static function map($things, $callback)
    {
        $outputStrings = self::mapStream(
            $things,
            function ($key, $value, $stream) use ($callback) {
                $data = $callback($key, $value);
                fwrite($stream, serialize($data));
            }
        );

        $results = [];
        foreach ($outputStrings as $key => $output) {
            if ($output === null || $output === '') {
                $results[$key] = null;
            } else {
                $results[$key] = unserialize($output);
            }
        }
        return $results;
    }

    /**
     * Forks the process count($things) times and executes the callback on each
     * entry in $things, all in parallel. Passes a stream to each callback and
     * collects and returns the string output from each stream.
     * @param mixed $things
     * @param mixed $callback
     */
    protected static function mapStream($things, $callback)
    {
        $children = [];

        foreach ($things as $key => $value) {
            $info = self::fork();
            if ($info['parent']) {
                $children[$key] = $info;
            } else {
                $callback($key, $value, $info['stream']);
                fclose($info['stream']);
                exit;
            }
        }

        return self::getChildrenOutput($children);
    }

    protected static function fork()
    {
        $results = [];
        $sockets = stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        $pid = pcntl_fork();

        if ($pid === -1) {
            exit('Could not fork');
        } elseif ($pid) {
            /* parent */
            fclose($sockets[1]);
            $results = [
             'stream' => $sockets[0],
             'parent' => true,
          ];
        } else {
            /* child */
            fclose($sockets[0]);
            $results = [
             'stream' => $sockets[1],
             'parent' => false,
          ];
        }

        return $results;
    }

    protected static function getChildrenOutput($children)
    {
        $output = [];
        foreach ($children as $i => $child) {
            $output[$i] = stream_get_contents($child['stream']);
        }
        return $output;
    }
}
