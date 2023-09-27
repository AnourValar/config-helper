<?php

namespace AnourValar\ConfigHelper;

class ConfigHelper
{
    /**
     * Prepares data for select's options
     *
     * @param mixed $data
     * @param mixed $selected
     * @param array $conditions
     * @param array $mapping
     * @return \AnourValar\ConfigHelper\SelectOptions
     */
    public function toSelect($data, $selected = null, array $conditions = null, array $mapping = null): SelectOptions
    {
        // data prepares
        if (is_string($data)) {
            $data = config($data);
        }

        // selected prepares
        $selected = (array) $selected;
        foreach ($selected as &$item) {
            $item = (string) $item;
        }
        unset($item);

        // mapping prepares
        $mapping = array_replace(config('config_helper.default_mapping'), (array) $mapping);

        // Handle
        return new SelectOptions($this->buildSelect($data, $selected, $conditions, $mapping), $selected);
    }

    /**
     * Gets filtered list of config keys
     *
     * @param mixed $config
     * @param array $conditions
     * @return array
     */
    public function keys($config, ?array $conditions = []): array
    {
        if (is_string($config)) {
            $config = config($config);
        }

        $result = [];
        foreach ($config as $key => $value) {
            if (!$this->conditionsPasses($conditions, $value, $key)) {
                continue;
            }

            $result[] = $key;
        }

        return $result;
    }

    /**
     * Gets a singleton-key of config
     *
     * @param mixed $config
     * @param array $conditions
     * @param bool $strict
     * @throws \LogicException
     * @return mixed
     */
    public function key($config, ?array $conditions = [], bool $strict = true)
    {
        $result = $this->keys($config, $conditions);

        if ($strict && count($result) != 1) {
            throw new \LogicException('Required key must be single.');
        }

        return $result[0] ?? null;
    }

    /**
     * Gets first key of config
     *
     * @param mixed $config
     * @param array $conditions
     * @return mixed
     */
    public function firstKey($config, ?array $conditions = [])
    {
        return $this->key($config, $conditions, false);
    }

    /**
     * Gets value from a singleton-key of config
     *
     * @param string $config
     * @param array $conditions
     * @param string $path
     * @param bool $strict
     * @return mixed
     */
    public function value(string $config, ?array $conditions = [], string $path = null, bool $strict = true)
    {
        $key = $this->key($config, $conditions, $strict);

        if (isset($path)) {
            return config("$config.$key.$path");
        }

        return config("$config.$key");
    }

    /**
     * Gets config with localized titles & filtered keys
     *
     * @param mixed $config
     * @param array $visibleKeys
     * @param mixed $transKeys
     * @return mixed
     */
    public function publish($config, array $visibleKeys, $transKeys = null)
    {
        if (is_string($config)) {
            $config = config($config);
        }

        $transKeys = (array) $transKeys;
        return $this->publishRecursive($config, array_unique(array_merge($visibleKeys, $transKeys)), $transKeys);
    }

    /**
     * Check if conditions passed
     *
     * @param array $conditions
     * @param mixed $item
     * @param mixed $key
     * @return bool
     */
    public function conditionsPasses(?array $conditions, $item, $key): bool
    {
        foreach ((array) $conditions as $field => $value) {
            if (is_numeric($field)) {
                $curr = $key;
            } else {
                $curr = $item;
                foreach (explode('.', $field) as $subField) {
                    $curr = $curr[$subField] ?? null;
                }
            }

            if ($value === true && $curr) {
                continue;
            }

            if ($value === false && ($curr === false || $curr === null || (is_array($curr) && !count($curr)))) {
                continue;
            }

            if ($value === null && !isset($curr)) {
                continue;
            }

            $curr = (array) $curr;
            $value = (array) $value;
            foreach ($curr as $single) {
                if (in_array($single, $value, true)) {
                    continue 2;
                }
            }
            foreach (array_keys($curr) as $single) {
                if (in_array($single, $value, true)) {
                    continue 2;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * @param iterable $data
     * @param array $selected
     * @param array $conditions
     * @param array $mapping
     * @return array
     */
    private function buildSelect(iterable $data, array $selected, ?array $conditions, array $mapping): array
    {
        $result = [];

        foreach ($data as $key => $item) {
            if ($mapping['value']) {
                $value = $this->extractMapper($item, $mapping['value'], true);
            } elseif ($item instanceof \Illuminate\Database\Eloquent\Model) {
                $value = $item[$item->getKeyName()];
            } else {
                $value = $key;
            }

            if (! in_array((string) $value, $selected, true)) {
                if (! $this->conditionsPasses($conditions, $item, $value)) {
                    continue;
                }

                if ($mapping['is_actual']) {
                    $isActual = $this->extractMapper($item, $mapping['is_actual'], false);

                    if (isset($isActual) && !$isActual) {
                        continue;
                    }
                }
            }

            if (! is_scalar($item)) {
                $title = $this->extractMapper($item, $mapping['title'], true);
            } else {
                $title = $item;
            }

            $option = [
                'title' => trans($title),
                'attributes' => (array) $this->extractMapper($item, $mapping['attributes'], false),
            ];

            if ($mapping['optgroup'] && $optgroup = $this->extractMapper($item, $mapping['optgroup'], false)) {
                $result[trans($optgroup)][$value] = $option;
            } else {
                $result[$value] = $option;
            }
        }

        return $result;
    }

    /**
     * @param mixed $item
     * @param string $mapper
     * @param bool $strict
     * @return mixed
     */
    private function extractMapper(mixed $item, string $mapper, bool $strict): mixed
    {
        if (isset($item[$mapper])) {
            return $item[$mapper];
        }

        foreach (explode('.', $mapper) as $part) {
            if (! $strict) {
                $item = ($item[$part] ?? null);
            } else {
                $item = $item[$part];
            }
        }

        return $item;
    }

    /**
     * @param array $config
     * @param array $visibleKeys
     * @param array $transKeys
     * @return array
     */
    private function publishRecursive(array $config, array $visibleKeys, array $transKeys): array
    {
        $hasVisible = false;
        foreach ($config as $key => $item) {
            if (in_array($key, $visibleKeys, true) || !$visibleKeys) {
                $hasVisible = true;
                break;
            }
        }

        foreach ($config as $key => &$item) {
            if ($hasVisible && !in_array($key, $visibleKeys, true)) {
                unset($config[$key]);
                continue;
            }

            if (is_array($item)) {
                $item = $this->publishRecursive($item, $visibleKeys, $transKeys);
            } elseif (in_array($key, $transKeys, true) && is_string($item)) {
                $item = trans($item);
            }
        }
        unset($item);

        return $config;
    }
}
