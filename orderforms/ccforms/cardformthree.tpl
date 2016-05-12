<div id="ccinputform"{if $selectedgatewaytype neq "CC"} style="display:none;"{/if}>
      <div class="alert alert-error alert-danger payment-errors" style="display:none;"></div>
<table width="100%" cellpadding="2">
<tr><td colspan="2"><label><input type="radio" name="ccinfo" value="useexisting" id="useexisting" onclick="useExistingCC()"{if $clientsdetails.cclastfour} checked{else} disabled{/if} /> {$LANG.creditcarduseexisting}{if $clientsdetails.cclastfour} ({$clientsdetails.cclastfour}){/if}</label><br />
<label><input type="radio" name="ccinfo" value="new" id="new" onclick="enterNewCC()"{if !$clientsdetails.cclastfour || $ccinfo eq "new"} checked{/if} /> {$LANG.creditcardenternewcard}</label></td></tr>
<tr class="newccinfo"{if $clientsdetails.cclastfour && $ccinfo neq "new"} style="display:none;"{/if}><td>{$LANG.creditcardcardnumber}</td><td><input type="text" class="card-number" style="width:80%;" autocomplete="off" /></td></tr>
<tr class="newccinfo"{if $clientsdetails.cclastfour && $ccinfo neq "new"} style="display:none;"{/if}><td>{$LANG.creditcardcardexpires}</td><td>
<select name="ccexpirymonth" id="ccexpirymonth" class="card-expiry-month newccinfo">
{foreach from=$months item=month}
<option{if $ccexpirymonth eq $month} selected{/if}>{$month}</option>
{/foreach}</select> / <select name="ccexpiryyear" class="card-expiry-year newccinfo">
{if !isset($expiryyears)}
{assign var="expiryyears" value=$years}
{/if}
{foreach from=$expiryyears item=year}
<option{if $ccexpiryyear eq $year} selected{/if}>{$year}</option>
{/foreach}
</select>
<input type="hidden" name="cccvv" value="123" />	
</td></tr>
<tr class="newccinfo" {if $clientsdetails.cclastfour && $ccinfo neq "new"} style="display:none;"{/if}><td width="100">{$LANG.creditcardcvvnumber}</td><td><input type="text" class="card-cvc" size="5" autocomplete="off" /></td></tr>
<tr class="newccinfo" {if $clientsdetails.cclastfour && $ccinfo neq "new"} style="display:none;"{/if}><td></td><td><a href="#" onclick="window.open('images/ccv.gif','','width=280,height=200,scrollbars=no,top=100,left=100');return false">{$LANG.creditcardcvvwhere}</a></td></tr>
</table>
</div>