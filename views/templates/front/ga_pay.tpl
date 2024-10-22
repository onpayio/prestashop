{*
* @author OnPay.io
* @copyright 2024 OnPay.io
* @license MIT
* 
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
*}

<style type="text/css">
    {if isset($apple_id)}
        #{$apple_id}-container {literal}{display:none}{/literal}
        #{$apple_id}-container.show {literal}{display:inherit}{/literal}
    {/if}
    {if isset($google_id)}
        #{$google_id}-container {literal}{display:none}{/literal}
        #{$google_id}-container.show {literal}{display:inherit}{/literal}
    {/if}
</style>
<script type="text/javascript">
    {if isset($apple_id)}
        let appleId="{$apple_id}";
    {/if}
    {if isset($google_id)}
        let googleId="{$google_id}";
    {/if}
</script>
