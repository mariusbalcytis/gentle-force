local key = KEYS[1]
local parametersList = cjson.decode(ARGV[1])
local now = tonumber(ARGV[2])
local totals = {}
local sub_keys = {}
local usages_available = {}
local valid_after = 0;

for i, parameters in ipairs(parametersList) do
    local tokens_per_usage = parameters[1]
    local bucket_size = parameters[2]
    local sub_key = tokens_per_usage .. ':' .. bucket_size
    local total = 0
    local empty_at = tonumber(redis.call('HGET', key, sub_key)) or 0

    if empty_at then
        total = math.max(0, empty_at - now)
    end

    total = total + tokens_per_usage

    if total > bucket_size then
        valid_after = math.max(valid_after, total - bucket_size)
    end

    table.insert(totals, total)
    table.insert(sub_keys, sub_key)
    table.insert(usages_available, total / tokens_per_usage)
end

if valid_after > 0 then
    return tostring(-valid_after)
end

local longest_duration = 0
local max_usages_available = 0
for i, total in ipairs(totals) do
    redis.call('HSET', key, sub_keys[i], now + total)
    longest_duration = math.max(longest_duration, total)
    max_usages_available = math.max(max_usages_available, usages_available[i])
end

redis.call('EXPIRE', key, math.ceil(longest_duration))

return tostring(math.floor(max_usages_available))
