<?php
/**
 * @copyright 2010 Payson
 */
//Minimal order values
$paysonInvoiceMinimalOrderValue = 30;

//Default values in array position 0
$paysonCurrenciesSupported = array('SEK', 'EUR');
$paysonLanguagesSupported = array('SV', 'EN', 'FI');

//shop texts, language dependent
$paysonShop['EN']['mark_button_img'] = "https://www.payson.se/sites/all/files/images/external/payson-72x29.jpg";
$paysonShop['SV']['mark_button_img'] = "https://www.payson.se/sites/all/files/images/external/payson-72x29.jpg";
$paysonShop['FI']['mark_button_img'] = "https://www.payson.se/sites/all/files/images/external/payson-72x29.jpg";

$paysonShop['EN']['check_out_w_payson'] = 'Checkout with Payson';
$paysonShop['SV']['check_out_w_payson'] = 'Betala med Payson';
$paysonShop['FI']['check_out_w_payson'] = 'Betala med Payson';

$paysonShop['EN']['read_more_link'] = '<a href="' . $paysonInfoLink . '" target="_blank">Read more</a>';
$paysonShop['SV']['read_more_link'] = '<a href="' . $paysonInfoLink . '" target="_blank">Läs mer</a>';
$paysonShop['FI']['read_more_link'] = '<a href="' . $paysonInfoLink . '" target="_blank">Läs mer</a>';

$paysonShop['EN']['order_id_from_text'] = 'Order: %s from ';
$paysonShop['SV']['order_id_from_text'] = 'Order: %s från ';
$paysonShop['FI']['order_id_from_text'] = 'Order: %s från ';

$paysonShop['EN']['order_id_from_text_short'] = 'Order: %s';
$paysonShop['SV']['order_id_from_text_short'] = 'Order: %s';
$paysonShop['FI']['order_id_from_text_short'] = 'Order: %s';


$paysonShop['EN']['mailtext_paysonreference'] = 'Payment Approved by Payson with Referece Number';
$paysonShop['SV']['mailtext_paysonreference'] = 'Betalning har genomförts via Payson med Referensnummer';
$paysonShop['FI']['mailtext_paysonreference'] = 'Betalning har genomförts via Payson med Referensnummer';

//do not increase text length on this, Prestashop use a max length of 32 chars and the Payson payment ref must also be included.
$paysonShop['EN']['paysonreference_ps'] = 'Payson RefNr: ';
$paysonShop['SV']['paysonreference_ps'] = 'Payson Refnr: ';
$paysonShop['FI']['paysonreference_ps'] = 'Payson Refnr: ';

//admin texts,
$paysonAdmin['EN']['text_admin_title'] = "Payson New API";
$paysonAdmin['SV']['text_admin_title'] = "Payson Nytt API";
$paysonAdmin['FI']['text_admin_title'] = "Payson Nytt API";

$paysonAdmin['EN']['text_catalog_title'] = "Payson";
$paysonAdmin['SV']['text_catalog_title'] = "Payson";
$paysonAdmin['FI']['text_catalog_title'] = "Payson";


$paysonAdmin['EN']['inv_text_catalog_title'] = "Payson Invoice";
$paysonAdmin['SV']['inv_text_catalog_title'] = "Payson Faktura";
$paysonAdmin['FI']['inv_text_catalog_title'] = "Payson Faktura";


$paysonAdmin['EN']['config_instruction1'] = '<strong>Payson</strong><br /><a href="' . $paysonSignInLink . '" target="_blank">Manage your Payson account.</a><br /><br /><font color="green">Configuration Instructions:</font><br />
  1. <a href="' . $paysonSignUpLink . '" target="_blank">Sign up for your Payson account - click here.</a><br />';
$paysonAdmin['SV']['config_instruction1'] = '<strong>Payson</strong><br /><a href="' . $paysonSignInLink . '" target="_blank">Hantera ditt Paysonkonto.</a><br /><br /><font color="green">Konfigureringsinstruktioner:</font><br />
  1. <a href="' . $paysonSignUpLink . '" target="_blank">Skapa ditt Paysonkonto - klicka här.</a><br />';
$paysonAdmin['SV']['config_instruction1'] = '<strong>Payson</strong><br /><a href="' . $paysonSignInLink . '" target="_blank">Hantera ditt Paysonkonto.</a><br /><br /><font color="green">Konfigureringsinstruktioner:</font><br />
  1. <a href="' . $paysonSignUpLink . '" target="_blank">Skapa ditt Paysonkonto - klicka här.</a><br />';

$paysonAdmin['EN']['config_instruction2'] = '2. ...and click "install" above to enable Payson support... and "edit" your Payson settings.';
$paysonAdmin['SV']['config_instruction2'] = '2. ...och klicka "install" ovan för att aktivera Payson support... och "edit" dina Paysoninställningar.';
$paysonAdmin['FI']['config_instruction2'] = '2. ...och klicka "install" ovan för att aktivera Payson support... och "edit" dina Paysoninställningar.';


$paysonAdmin['EN']['config_instruction2_vm'] = '2. ...and fill in form below to enable Payson support... and "save" your Payson settings.';
$paysonAdmin['SV']['config_instruction2_vm'] = '2. ...och fyll i formulär nedan för att aktivera Payson support... och "spara" dina Paysoninställningar.';
$paysonAdmin['FI']['config_instruction2_vm'] = '2. ...och fyll i formulär nedan för att aktivera Payson support... och "spara" dina Paysoninställningar.';

$paysonAdmin['EN']['config_instruction3'] = '</ul><font color="green"><hr /><strong>Requirements:</strong></font><br /><hr />*<strong>Payson Account</strong> (<a href="' . $paysonSignUpLink . '" target="_blank">click to signup</a>)<br />*<strong>*<strong>Port 80</strong> is used for bidirectional communication with the gateway, so must be open on your host\'s router/firewall<br />*<strong>Settings</strong> must be configured as described above.';
$paysonAdmin['SV']['config_instruction3'] = '</ul><font color="green"><hr /><strong>Krav:</strong></font><br /><hr />*<strong>Paysonkonto</strong> (<a href="' . $paysonSignUpLink . '" target="_blank">klicka här för att skapa</a>)<br />*<strong>*<strong>Port 80</strong> används för dubbelriktad kommunikation med Paysons server, så den måste vara öppen i din host\'s router/firewall<br />*<strong>Inställningar</strong> måste konfigureras enligt ovan beskrivet.';
$paysonAdmin['FI']['config_instruction3'] = '</ul><font color="green"><hr /><strong>Krav:</strong></font><br /><hr />*<strong>Paysonkonto</strong> (<a href="' . $paysonSignUpLink . '" target="_blank">klicka här för att skapa</a>)<br />*<strong>*<strong>Port 80</strong> används för dubbelriktad kommunikation med Paysons server, så den måste vara öppen i din host\'s router/firewall<br />*<strong>Inställningar</strong> måste konfigureras enligt ovan beskrivet.';

$paysonAdmin['EN']['accept_payson'] = 'Do you want to accept Payson payments?';
$paysonAdmin['SV']['accept_payson'] = 'Vill du ta emot betalningar med Payson?';
$paysonAdmin['FI']['accept_payson'] = 'Vill du ta emot betalningar med Payson?';

$paysonAdmin['EN']['enable_payson'] = 'Enable Payson Module';
$paysonAdmin['SV']['enable_payson'] = 'Aktivera Paysonmodul';
$paysonAdmin['FI']['enable_payson'] = 'Aktivera Paysonmodul';

$paysonAdmin['EN']['agentid_head'] = 'Agent Id';
$paysonAdmin['SV']['agentid_head'] = 'Agentid';
$paysonAdmin['FI']['agentid_head'] = 'Agentid';

$paysonAdmin['EN']['agentid_text'] = 'Agent Id for your Payson account.';
$paysonAdmin['SV']['agentid_text'] = 'AgentId för ditt Paysonkonto.';
$paysonAdmin['FI']['agentid_text'] = 'AgentId för ditt Paysonkonto.';

$paysonAdmin['EN']['selleremail_head'] = 'Seller Email';
$paysonAdmin['EN']['selleremail_text'] = 'Email address for your Payson account.<br />NOTE: This must match <strong>EXACTLY </strong>the primary email address on your Payson account settings.';
$paysonAdmin['SV']['selleremail_head'] = 'Säljarens Email';
$paysonAdmin['SV']['selleremail_text'] = 'Emailadress för ditt Paysonkonto.<br />OBS: Denna måste vara <strong>identisk </strong>med den emailadress som för ditt Paysonkonto.';
$paysonAdmin['FI']['selleremail_head'] = 'Säljarens Email';
$paysonAdmin['FI']['selleremail_text'] = 'Emailadress för ditt Paysonkonto.<br />OBS: Denna måste vara <strong>identisk </strong>med den emailadress som för ditt Paysonkonto.';

$paysonAdmin['EN']['md5key_head'] = 'MD5 Key';
$paysonAdmin['EN']['md5key_text'] = 'MD5 Key for your Payson account.';
$paysonAdmin['SV']['md5key_head'] = 'MD5nyckel';
$paysonAdmin['SV']['md5key_text'] = 'MD5nyckel för ditt Paysonkonto';
$paysonAdmin['FI']['md5key_head'] = 'MD5nyckel';
$paysonAdmin['FI']['md5key_text'] = 'MD5nyckel för ditt Paysonkonto';

$paysonAdmin['EN']['paymentmethods_some'] = 'Some, as below';
$paysonAdmin['SV']['paymentmethods_some'] = 'Några enligt nedan';
$paysonAdmin['FI']['paymentmethods_some'] = 'Några enligt nedan';

$paysonAdmin['EN']['vm_extrainfo_text'] = 'If the Payment Extra Info field is blank you must click this button below!';
$paysonAdmin['SV']['vm_extrainfo_text'] = 'Om fältet Payment Extra Info nedan är tomt måste du klicka på knappen nedan!';
$paysonAdmin['FI']['vm_extrainfo_text'] = 'Om fältet Payment Extra Info nedan är tomt måste du klicka på knappen nedan!';

$paysonAdmin['EN']['vm_extrainfo_button_text'] = 'Populate field below automatic';
$paysonAdmin['SV']['vm_extrainfo_button_text'] = 'Fyll i fältet nedan automatiskt';
$paysonAdmin['FI']['vm_extrainfo_button_text'] = 'Fyll i fältet nedan automatiskt';

$paysonAdmin['EN']['custommess_head'] = 'Custom message';
$paysonAdmin['EN']['custommess_text'] = 'Custom message, common for all orders.';
$paysonAdmin['SV']['custommess_head'] = 'Meddelande';
$paysonAdmin['SV']['custommess_text'] = 'Meddelande, likadant för alla ordrar.';
$paysonAdmin['FI']['custommess_head'] = 'Meddelande';
$paysonAdmin['FI']['custommess_text'] = 'Meddelande, likadant för alla ordrar.';

$paysonAdmin['EN']['module_uninstalled'] = 'Payson Uninstalled';
$paysonAdmin['SV']['module_uninstalled'] = 'Payson avinstallerad';
$paysonAdmin['FI']['module_uninstalled'] = 'Payson avinstallerad';

$paysonAdmin['EN']['module_installed'] = 'Payson Installed';
$paysonAdmin['SV']['module_installed'] = 'Payson installerad';
$paysonAdmin['FI']['module_installed'] = 'Payson installerad';


//db table names
$paysonDbTableOrderEembedded = "payson_embedded_order";

?>