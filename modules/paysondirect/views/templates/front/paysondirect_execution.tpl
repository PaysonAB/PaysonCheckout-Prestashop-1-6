{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='paysondirect'}">{l s='Checkout' mod='paysondirect'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Paysondirect payment' mod='paysondirect'}
{/capture}

<iframe id='checkoutIframe' style="height:{Tools::getValue('height')}{Tools::getValue('height_type')}; width:{Tools::getValue('width')}{Tools::getValue('width_type')};"  scrolling="no" name='checkoutIframe' src={Tools::getValue('snippetUrl')}>
</iframe>
