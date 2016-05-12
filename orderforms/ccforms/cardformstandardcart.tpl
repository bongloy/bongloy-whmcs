                <div id="creditCardInputFields"{if $selectedgatewaytype neq "CC"} class="hidden"{/if}>
                        <div class="row margin-bottom">
                            <div class="col-sm-12">
                                <div class="text-center">
                                    <label class="radio-inline">
                                        <input type="radio" name="ccinfo" value="useexisting" id="useexisting" {if $clientsdetails.cclastfour} checked{else} disabled{/if} />
                                        {$LANG.creditcarduseexisting}
                                        {if $clientsdetails.cclastfour}
                                            ({$clientsdetails.cclastfour})
                                        {/if}
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="ccinfo" value="new" id="new" {if !$clientsdetails.cclastfour || $ccinfo eq "new"} checked{/if} />
                                        {$LANG.creditcardenternewcard}
                                    </label>
                                </div>
                            </div>
                        </div>
                    <div id="newCardInfo" class="row{if $clientsdetails.cclastfour && $ccinfo neq "new"} hidden{/if}">
                        <div class="alert alert-danger payment-errors" style="display:none;"></div>
                        
                        <div class="col-sm-6">
                            <div class="form-group prepend-icon">
                                <label for="inputCardNumber" class="field-icon">
                                    <i class="fa fa-credit-card"></i>
                                </label>
                                <input type="tel" id="inputCardNumber" class="field card-number" placeholder="{$LANG.orderForm.cardNumber}" autocomplete="cc-number">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group prepend-icon">
                                <label for="inputCardExpiry" class="field-icon">
                                    <i class="fa fa-calendar"></i>
                                </label>
                                <input type="tel" id="inputCardExpiry" class="field" placeholder="MM / YY{if $showccissuestart} ({$LANG.creditcardcardexpires}){/if}" autocomplete="cc-exp">
                                <input type="hidden" name="ccexpirymonth" id="ccexpirymonth">
                                <input type="hidden" name="ccexpiryyear" id="ccexpiryyear">
                                
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group prepend-icon">
                                <label for="inputCardCVV" class="field-icon">
                                    <i class="fa fa-barcode"></i>
                                </label>
                                <input type="tel" id="inputCardCVV" class="field card-cvc" placeholder="{$LANG.orderForm.cvv}" autocomplete="cc-cvc">
                                 <input type="hidden" name="cccvv" value="123" />
                            </div>
                        </div>
                    </div>
                    <div id="existingCardInfo" class="row{if !$clientsdetails.cclastfour || $ccinfo eq "new"} hidden{/if}">
                    </div>
                </div>