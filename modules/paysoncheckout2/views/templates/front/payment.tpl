{*
* 2018 Payson AB
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
*
*  @author    Payson AB <integration@payson.se>
*  @copyright 2018 Payson AB
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='paysoncheckout2'}">{l s='Checkout' mod='paysoncheckout2'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Payment' mod='paysoncheckout2'}
{/capture}

<script type="text/javascript">
    // <![CDATA[
    var pcourl = '{$pcoUrl|escape:'javascript':'UTF-8'}';
    var pco_checkout_id = '{$pco_checkout_id|escape:'javascript':'UTF-8'}';
    var id_cart = '{$id_cart|intval}';
    var validateurl = '{$validateUrl|escape:'javascript':'UTF-8'}';
    var paymenturl = '{$paymentUrl|escape:'javascript':'UTF-8'}';
    // ]]>
</script>

{block name="content"}
    <section>
        <div id="paysonpaymentwindow">
            {$snippet nofilter}{* HTML comment, no escaping necessary *}
        </div>
    </section>
{/block}
