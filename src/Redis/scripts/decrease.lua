local key = KEYS[1]
local parametersList = cjson.decode(ARGV[1])

for i, parameters in ipairs(parametersList) do
    local tokens_per_usage = parameters[1]
    local bucket_size = parameters[2]
    local sub_key = tokens_per_usage .. ':' .. bucket_size
    redis.call('HINCRBYFLOAT', key, sub_key, -tokens_per_usage)
end

return 0
