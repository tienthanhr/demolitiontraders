<?php
use XeroPHP\Remote\OAuth2\Client as XeroClient;
use XeroPHP\Application;
use XeroPHP\Models\Accounting\Invoice;
use XeroPHP\Models\Accounting\LineItem;
use XeroPHP\Models\Accounting\Contact;

function sendInvoiceToXero($lines) {
    global $xeroClientId, $xeroClientSecret, $xeroRedirect;
    $provider = new XeroClient([
        'clientId'     => $xeroClientId,
        'clientSecret' => $xeroClientSecret,
        'redirectUri'  => $xeroRedirect,
        'urlAuthorize' => 'https://login.xero.com/identity/connect/authorize',
        'urlAccessToken' => 'https://identity.xero.com/connect/token'
    ]);

    $xero = new Application($provider);

    $invoice = new Invoice($xero);
    $invoice->setType('ACCREC')
            ->setContact((new Contact($xero))->setName("Online Web Order"))
            ->setStatus('AUTHORISED');

    foreach ($lines as $line) {
        $invoice->addLineItem(
            (new LineItem($xero))
                ->setDescription($line['name'])
                ->setItemCode($line['item_code'])
                ->setQuantity($line['qty'])
                ->setUnitAmount($line['price'])
        );
    }

    $xero->save($invoice);
}
?>