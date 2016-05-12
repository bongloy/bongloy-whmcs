<div id="ccinputform" class="form-horizontal{if $selectedgatewaytype neq "CC"} hidden{/if}">
<div class="alert alert-danger payment-errors" style="display:none;"></div>
                {if $clientsdetails.cclastfour}
                    <div class="form-group">
                        <div class="col-sm-7 col-sm-offset-5">
                            <label class="radio-inline"><input type="radio" name="ccinfo" value="useexisting" id="useexisting" onclick="useExistingCC()"{if $ccinfo ne "new"} checked{elseif !$clientsdetails.cclastfour} disabled="disabled"{/if} /> {$LANG.creditcarduseexisting}{if $clientsdetails.cclastfour} ({$clientsdetails.cclastfour}){/if}</label><br />
                            <label class="radio-inline"><input type="radio" name="ccinfo" value="new" id="new" onclick="enterNewCC()"{if $ccinfo eq "new"} checked{/if} /> {$LANG.creditcardenternewcard}</label>
                        </div>
                    </div>
                {else}
                    <div class="form-group">
                        <div class="col-sm-7 col-sm-offset-5">
                            <label class="radio-inline"><input type="radio" name="ccinfo" value="useexisting" id="useexisting" onclick="useExistingCC()"{if $ccinfo eq "useexisting"} checked{elseif !$clientsdetails.cclastfour} disabled="disabled"{/if} /> {$LANG.creditcarduseexisting}{if $clientsdetails.cclastfour} ({$clientsdetails.cclastfour}){/if}</label><br />
                            <label class="radio-inline"><input type="radio" name="ccinfo" value="new" id="new" onclick="enterNewCC()" checked /> {$LANG.creditcardenternewcard}</label>
                        </div>
                    </div>
                {/if}

                <div class="form-group new-card-info{if $ccinfo eq "useexisting"} hidden{/if}">
                    <label for="inputCardNumber" class="col-sm-5 control-label">{$LANG.creditcardcardnumber}</label>
                    <div class="col-sm-5">
                        <input type="text"  id="inputCardNumber" value="{$ccnumber}" autocomplete="off" class="form-control card-number" />
                    </div>
                </div>
                <div class="form-group new-card-info{if $ccinfo eq "useexisting"} hidden{/if}">
                    <label for="inputCardExpiry" class="col-sm-5 control-label">{$LANG.creditcardcardexpires}</label>
                    <div class="col-sm-7 form-inline-always">
                        <select name="ccexpirymonth" id="inputCardExpiry" class="form-control select-inline card-expiry-month">
                            {foreach from=$months item=month}
                                <option{if $ccexpirymonth eq $month} selected{/if}>{$month}</option>
                            {/foreach}
                        </select> / 
                        <select name="ccexpiryyear" class="form-control select-inline card-expiry-year">
                            {foreach from=$expiryyears item=year}
                                <option{if $ccexpiryyear eq $year} selected{/if}>{$year}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>

                <div class="form-group new-card-info{if $ccinfo eq "useexisting"} hidden{/if}">
                    <label for="inputCardCvv" class="col-sm-5 control-label">{$LANG.creditcardcvvnumber}</label>
                    <div class="col-sm-7 row">
                        <div class="col-md-6 col-lg-5">
                            <div class="input-group">
                                <input type="text" id="inputCardCvv" autocomplete="off" class="form-control input-mini card-cvc" />
                                <input type="hidden" name="cccvv" value="123" />
                                <span class="input-group-addon"><a href="#" onclick="window.open('assets/img/ccv.gif','','width=280,height=200,scrollbars=no,top=100,left=100');return false">{$LANG.creditcardcvvwhere}</a></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
