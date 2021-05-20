<?php

namespace AnourValar\ConfigHelper;

class ConfigHelper
{
    /**
     * Prepares data for select's options
     *
     * @param mixed $data
     * @param array $prepends
     * @param array $conditions
     * @param mixed $selected
     * @param string $keyValue
     * @param string $keyTitle
     * @param string $keyOptgroup
     * @param string $keyAttributes
     * @param string $keyActual
     * @return SelectOptions
     */
    public function toSelect(
        $data,
        array $prepends = null,
        array $conditions = null,
        $selected = null,
        string $keyValue = null,
        ?string $keyTitle = 'title',
        ?string $keyOptgroup = 'optgroup',
        ?string $keyAttributes = 'attributes',
        ?string $keyActual = 'is_actual'
    ): SelectOptions {
        if (is_string($data)) {
            $data = config($data);
        }
        $selected = (array) $selected;
        foreach ($selected as &$item) {
            $item = (string) $item;
        }
        unset($item);

        $result = (array)$prepends;

        foreach ($data as $key => $item) {
            if (!is_null($keyValue)) {
                $curr = $item[$keyValue];
            } elseif ($item instanceof \Illuminate\Database\Eloquent\Model) {
                $curr = $item[$item->getKeyName()];
            } else {
                $curr = $key;
            }

            if (!in_array((string) $curr, $selected, true)) {
                if (! $this->conditionsPasses($conditions, $item, $curr)) {
                    continue;
                }

                if ($keyActual && isset($item[$keyActual]) && !$item[$keyActual]) {
                    continue;
                }
            }

            if ($keyAttributes && isset($item[$keyAttributes])) {
                $option = [
                    'title' => trans($item[$keyTitle]),
                    'attributes' => $item[$keyAttributes],
                ];
            } else {
                $option = trans($item[$keyTitle]);
            }

            if ($keyOptgroup && isset($item[$keyOptgroup])) {
                $result[trans($item[$keyOptgroup])][$curr] = $option;
            } else {
                $result[$curr] = $option;
            }
        }

        return new SelectOptions($result, $selected);
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
     * @param boolean $strict
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
     * Gets config with localized titles
     *
     * @param mixed $config
     * @param array $transKeys
     * @param array $visibleKeys
     * @return mixed
     */
    public function trans($config, array $transKeys, array $visibleKeys = [])
    {
        if (is_string($config)) {
            $config = config($config);
        }

        if (!is_array($config)) {
            return $config;
        }

        $visibleKeys = array_unique(array_merge($transKeys, $visibleKeys));
        return $this->localizeRecursive($config, $transKeys, $visibleKeys);
    }

    /**
     * Check if conditions passed
     *
     * @param array $conditions
     * @param mixed $item
     * @param mixed $key
     * @return boolean
     */
    public function conditionsPasses(?array $conditions, $item, $key): bool
    {
        foreach ((array)$conditions as $field => $value) {
            if (is_numeric($field)) {
                $curr = $key;
            } else {
                $curr = $item[$field] ?? null;
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

            $curr = (array)$curr;
            $value = (array)$value;
            if (array_intersect(array_keys($curr), $value) || array_intersect($curr, $value)) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * @param array $config
     * @param array $transKeys
     * @param array $visibleKeys
     * @param integer $level
     * @return array
     */
    private function localizeRecursive(array $config, array $transKeys, $visibleKeys, int $level = 1): array
    {
        foreach ($config as $key => &$item) {
            if ($level == 2 && !in_array($key, $visibleKeys, true)) {
                unset($config[$key]);
                continue;
            }

            if (is_array($item)) {
                $item = $this->localizeRecursive($item, $transKeys, $visibleKeys, ($level + 1));
            } elseif (in_array($key, $transKeys, true) && is_string($item)) {
                $item = trans($item);
            }
        }
        unset($item);

        return $config;
    }
}
