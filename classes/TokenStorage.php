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


class TokenStorage implements \OnPay\TokenStorageInterface
{
    /**
     * Should return the stored token, or null if no token is stored.
     *
     * @return null|string
     */
    public function getToken()
    {
        if(!Configuration::get('ONPAY_TOKEN')) {
            return null;
        }
        // ThirtyBees uses the pSQL alias method for escaping strings when using updateValue.
        // We need to unescape the string when getting the value again within the same runtime, since the escaped value is 'cached' and wrongfully returned.
        // This is a reported bug, that has been fixed in releases of ThirtyBees later than 1.0.8.
        // This workaround does not have any implications for either Prestashop nor ThirtyBees v. 1.0.8+
        return str_replace('\"', '"', Configuration::get('ONPAY_TOKEN'));
    }

    /**
     * This method is responsible for saving the token to permanent storage.
     *
     * It is up to implementor where to store it, could be database, flat file or something else.
     * The token will change on an ongoing basis, whenever the access token expires and is refreshed.
     *
     * @param string $token
     * @return mixed
     */
    public function saveToken($token)
    {
        Configuration::updateValue('ONPAY_TOKEN', $token);
    }
}
