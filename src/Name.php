<?php

namespace Vokativ;

use \InvalidArgumentException;
use \RuntimeException;
define(
    'VOKATIV_DATA_DIR',
    __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR
);

class Name
{
    /**
     * Vrací jméno vyskloňované do 5. pádu
     * @param string $name Jméno v původním tvaru
     * @param boolean|null $isWoman
     * @param boolean|null $isLastName
     * @return string Jméno v 5. pádu
     */
    public function vokativ(string $name, bool $isWoman = null, bool $isLastName = null): string
    {
        if (gettype($name) !== 'string') {
            throw new InvalidArgumentException('`$name` has to be string');
        }
        $name = mb_strtolower($name, 'UTF-8');

        if (is_null($isWoman)) {
            $isWoman = !$this->isMale($name);
        }

        if ($isWoman) {
            if (is_null($isLastName)) {
                list($match, $type) = $this->getMatchingSuffix(
                    $name,
                    $this->getWomanFirstVsLastNameSuffixes()
                );

                $isLastName = $type === 'l';
            }

            if ($isLastName) {
                return $this->vokativWomanLastName($name);
            }
            return $this->vokativWomanFirstName($name);
        }

        return $this->vokativMan($name);
    }

    /**
     * Na základě jména nebo přijmení rozhodne o pohlaví
     * @param string $name Jméno v prvním pádu
     * @return boolean Rozhodne, jeslti je jméno mužské
     */
    public function isMale(string $name): bool
    {
        if (gettype($name) !== 'string') {
            throw new InvalidArgumentException('`$name` has to be string');
        }
        $name = mb_strtolower($name, 'UTF-8');

        list($match, $sex) = $this->getMatchingSuffix(
            $name,
            $this->getManVsWomanSuffixes()
        );

        return $sex !== 'w';
    }

    protected function vokativMan(string $name): string
    {
        list($match, $suffix) = $this->getMatchingSuffix(
            $name,
            $this->getManSuffixes()
        );

        if ($match) {
            $name = mb_substr($name, 0, -1 * mb_strlen($match));
        }

        return $name . $suffix;
    }

    protected function vokativWomanFirstName(string $name): string
    {
        if (mb_substr($name, -1) === 'a') {
            return mb_substr($name, 0, -1) . 'o';
        }
        return $name;
    }

    protected function vokativWomanLastName(string $name): string
    {
        return $name;
    }

    protected function getMatchingSuffix(string $name, $suffixes): array
    {
        // it is important(!) to try suffixes from longest to shortest
        foreach (range(mb_strlen($name), 1) as $length) {
            $suffix = mb_substr($name, -1 * $length);
            if (array_key_exists($suffix, $suffixes)) {
                return [$suffix, $suffixes[$suffix]];
            }
        }
        return ['', $suffixes['']];
    }

    protected $_manSuffixes = null;
    protected $_manVsWomanSuffixes = null;
    protected $_womanFirstVsLastSuffixes = null;

    protected function getManSuffixes()
    {
        if (is_null($this->_manSuffixes)) {
            $this->_manSuffixes = $this->readSuffixes('man_suffixes');
        }
        return $this->_manSuffixes;
    }

    protected function getManVsWomanSuffixes()
    {
        if (is_null($this->_manVsWomanSuffixes)) {
            $this->_manVsWomanSuffixes =
                $this->readSuffixes('man_vs_woman_suffixes');
        }
        return $this->_manVsWomanSuffixes;
    }

    protected function getWomanFirstVsLastNameSuffixes()
    {
        if (is_null($this->_womanFirstVsLastSuffixes)) {
            $this->_womanFirstVsLastSuffixes =
                $this->readSuffixes('woman_first_vs_last_name_suffixes');
        }
        return $this->_womanFirstVsLastSuffixes;
    }

    protected function readSuffixes($file)
    {
        $filename = VOKATIV_DATA_DIR . $file;
        if (!file_exists($filename)) {
            throw new RuntimeException('Data file ' . $filename . 'not found');
        }
        return unserialize(file_get_contents($filename));
    }
}