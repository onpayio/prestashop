<?php
/**
 * MIT License
 *
 * Copyright (c) 2019 OnPay.io
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

class CurrencyHelper
{
    /** @var ISO4217 $converter */
    private $converter;

    public function __construct()
    {
        $this->converter = new \Alcohol\ISO4217();
    }

    /**
     * @param int $amount
     * @param string $currency
     * @param string $decimalSeparator
     * @return int|mixed|string
     */
    public function minorToMajor($amount, $currency, $decimalSeparator = '.') {
        $currencyConverter = (object) $this->converter->getByNumeric($currency);
        $amount = strval($amount);
        if ($currencyConverter->exp > 0) {
            $newAmount = str_pad($amount, $currencyConverter->exp + 1, '0', STR_PAD_LEFT);
            return substr_replace($newAmount, $decimalSeparator, (0 - $currencyConverter->exp), 0);
        }
        return $amount;
    }

    /**
     * @param string $amount
     * @param string $currency
     * @param string $separator
     * @return int
     */
    public function majorToMinor($amount, $currency, $separator) {
        $currencyConverter = (object) $this->converter->getByNumeric($currency);
        $fraction = '';
        for ($i = 0; $i < $currencyConverter->exp; $i++) {
            $fraction .= '0';
        }
        $amountArr = explode($separator, $amount);
        $integer = $amountArr[0];
        if (array_key_exists(1, $amountArr)) {
            $amountFraction = substr($amountArr[1], 0, $currencyConverter->exp);
            $fraction = substr_replace($fraction, $amountFraction, 0, strlen($amountFraction));
        }
        return intval($integer . $fraction);
    }

    /**
     * @param $numeric
     * @return object
     */
    public function fromNumeric($numeric) {
        // We'll add missing trailing zeroes.
        $numeric = (string)$numeric;
        $currencyNumeric = $numeric;
        if (1 === strlen($numeric)) {
            $currencyNumeric = '00' . $numeric;
        } else if (2 === strlen($numeric)) {
            $currencyNumeric = '0' . $numeric;
        }

        try {
            return (object)$this->converter->getByNumeric($currencyNumeric);
        } catch(\DomainException $e) {
            return null;
        } catch(\OutOfBoundsException $e) {
            return null;
        }
    }
}
