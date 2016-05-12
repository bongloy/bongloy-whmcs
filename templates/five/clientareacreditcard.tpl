<script type="text/javascript" src="{$BASE_PATH_JS}/CreditCardValidation.js"></script>

{include file="$template/pageheader.tpl" title=$LANG.clientareanavccdetails}

{include file="$template/clientareadetailslinks.tpl"}

{if $remoteupdatecode}

  <div align="center">
    {$remoteupdatecode}
  </div>

{else}

{if $successful}
<div class="alert alert-success">
    <p>{if $deletecc}{$LANG.creditcarddeleteconfirmation}{else}{$LANG.changessavedsuccessfully}{/if}</p>
</div>
{/if}

{if $errormessage}
<div class="alert alert-error">
    <p class="bold">{$LANG.clientareaerrors}</p>
    <ul>
        {$errormessage}
    </ul>
</div>
{/if}

    {if $card_error}
    <div class="alert alert-danger">
     Your card could not be saved, please try again or contact support.
    </div>
    {/if}

    <div class="alert alert-danger payment-errors" style="display:none;"></div>    

  <fieldset class="onecol">

    <div class="control-group">
        <label class="control-label">{$LANG.creditcardcardtype}</label>
        <div class="controls">
            <input type="text" value="{$cardtype}" disabled="true" class="input-medium" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label">{$LANG.creditcardcardnumber}</label>
        <div class="controls">
            <div style="float:left">
                <input type="text" value="{$cardnum}" disabled="true" />
            </div>
            {if $allowcustomerdelete && $cardtype}
            <div style="float:left;margin-left:25px;">
                <form method="post" action="{$smarty.server.PHP_SELF}?action=creditcard">
                <input type="submit" name="remove" value="{$LANG.creditcarddelete}" class="btn btn-danger" />
                </form>
            </div>
{/if}
            <div class="clear"></div>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label">{$LANG.creditcardcardexpires}</label>
        <div class="controls">
            <input type="text" value="{$cardexp}" disabled="true" class="input-small" />
        </div>
    </div>
{if $cardstart}
    <div class="control-group">
        <label class="control-label">{$LANG.creditcardcardstart}</label>
        <div class="controls">
            <input type="text" value="{$cardstart}" disabled="true" class="input-small" />
        </div>
    </div>
{/if}{if $cardissuenum}
    <div class="control-group">
        <label class="control-label">{$LANG.creditcardcardissuenum}</label>
        <div class="controls">
            <input type="text" value="{$cardissuenum}" disabled="true" class="input-mini" />
        </div>
    </div>
{/if}
  </fieldset>

<div class="styled_title"><h3>{$LANG.creditcardenternewcard}</h3></div>

  <br />

<form class="form-horizontal" method="post"  action="modules/gateways/stripe-php/stripesave.php" id="payment-form" >

  <fieldset class="onecol">


    <div class="control-group">
        <label class="control-label" for="ccnumber">{$LANG.creditcardcardnumber}</label>
        <div class="controls">
            <input type="text" class="card-number2" id="ccnumber" autocomplete="off" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="ccexpirymonth">{$LANG.creditcardcardexpires}</label>
        <div class="controls">
            <select name="ccexpirymonth" id="ccexpirymonth" class="card-expiry-month">{foreach from=$months item=month}<option>{$month}</option>{/foreach}</select> / <select name="ccexpiryyear" class="card-expiry-year">{foreach from=$expiryyears item=year}<option>{$year}</option>{/foreach}</select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label">{$LANG.creditcardcvvnumber}</label>
        <div class="controls">
            <input type="text" id="cardcvv" maxlength="4" class="input-mini card-cvc" autocomplete="off" />
        </div>
    </div>


  </fieldset>

  <div class="form-actions">
    <input class="btn btn-primary submit-button" type="submit" id="submit-button" value="{$LANG.clientareasavechanges}" />
    <input class="btn" type="reset" value="{$LANG.cancel}" />
  </div>

</form>

{/if}
