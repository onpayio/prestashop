<?php
/**
 * MIT License
 *
 * Copyright (c) 2023 OnPay.io
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */


class Release {
    /**
     * @var int $lastCheck
     */
    private $lastCheck = 0;

    /**
     * @var string $latestVersion
     */
    private $latestVersion = '';

    /**
     * @var string $latestDownload
     */
    private $latestDownload = '';

    /**
     * @return Release
     */
    public static function fromString(string $string) {
        $values = json_decode($string, true);
        $release = new Release;
        
        if(array_key_exists('lastCheck', $values)) {
            $release->setLastCheck(intval($values['lastCheck']));
        }
        if(array_key_exists('latestVersion', $values)) {
            $release->setLatestVersion($values['latestVersion']);
        }
        if(array_key_exists('latestDownload', $values)) {
            $release->setLatestDownload($values['latestDownload']);
        }

        return $release;
    }

    /**
     * @return string
     */
    public function toString() {
        return json_encode([
            'lastCheck' => $this->getLastCheck(),
            'latestVersion' => $this->getLatestVersion(),
            'latestDownload' => $this->getLatestDownload(),
        ]);
    }

    /**
     * @return int
     */
    public function getLastCheck() {
        return $this->lastCheck;
    }

    /**
     * @param int $lastCheck
     */
    public function setLastCheck(int $lastCheck) {
        $this->lastCheck = $lastCheck;
    }

    /**
     * @return string
     */
    public function getLatestVersion() {
        return $this->latestVersion;
    }

    /**
     * @param string $latestVersion
     */
    public function setLatestVersion(string $latestVersion) {
        $this->latestVersion = $latestVersion;
    }

    /**
     * @return string
     */
    public function getLatestDownload() {
        return $this->latestDownload;
    }

    /**
     * @param string $latestDownload
     */
    public function setLatestDownload(string $latestDownload) {
        $this->latestDownload = $latestDownload;
    }
}
