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
     * @param array $keys
     * @return \AnourValar\ConfigHelper\SelectOptions
     */
    public function toSelect(
        $data,
        array $prepends = null,
        array $conditions = null,
        $selected = null,
        array $keys = null
    ): SelectOptions {
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

        // keys prepares
        $defaultKeys = [
            'value' => null, 'title' => 'title', 'optgroup' => 'optgroup', 'attributes' => 'attributes', 'is_actual' => 'is_actual'
        ];
        $keys = array_replace($defaultKeys, (array) $keys);

        // Handle
        $result = [];
        $this->buildSelect($result, (array) $prepends, [], [], $defaultKeys);
        $this->buildSelect($result, $data, $conditions, $selected, $keys);

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
     * @param array $result
     * @param iterable $data
     * @param array $conditions
     * @param array $selected
     * @param array $keys
     * @return void
     */
    private function buildSelect(array &$result, iterable $data, ?array $conditions, array $selected, array $keys): void
    {
        foreach ($data as $key => $item) {
            if ($keys['value']) {
                $value = $item[$keys['value']];
            } elseif ($item instanceof \Illuminate\Database\Eloquent\Model) {
                $value = $item[$item->getKeyName()];
            } else {
                $value = $key;
            }

            if (! in_array((string) $value, $selected, true)) {
                if (! $this->conditionsPasses($conditions, $item, $value)) {
                    continue;
                }

                if ($keys['is_actual'] && isset($item[$keys['is_actual']]) && !$item[$keys['is_actual']]) {
                    continue;
                }
            }

            $option = ['title' => trans($item[$keys['title']]), 'attributes' => ($item[$keys['attributes']] ?? [])];

            if ($keys['optgroup'] && isset($item[$keys['optgroup']])) {
                $result[trans($item[$keys['optgroup']])][$value] = $option;
            } else {
                $result[$value] = $option;
            }
        }
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
