@if(!empty($listing->status) && $listing->status=='Active')
@if(Auth::user() || 1==1)
@push('all-modals')
<div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog" aria-labelledby="schedulegModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                        <div class="modal-header">
                                <!--<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>-->
                                <h2 class="modal-title">Please confirm your details</h2>
                        </div>
                        
                        <div class="modal-body">
                                <form id="showingReq_form" class="listing-detail__showingReq showingReq_form" autocomplete="off" method="post" action="">
                                        <input type="hidden" name="listingid" value="{{$listing->listingid}}">
                                        <input type="hidden" name="dateone" value="" id="preferredDate">
                                        <input type="hidden" name="timeone" value="" id="preferredTime">
                                        <input type="hidden" nameXX="scheduleRealtor" name="agent-check" value="" id="scheduleRealtor">
                                        <input type="hidden" nameXX="schedulePreApprovedMortgage" name="approved-check" value="" id="schedulePreApprovedMortgage">
                                        <div class="row">
                                                <div class="col-xs-12">
                                                        <input type="text" name="firstname" placeholder="Name" value="{{trim($firstname.' '.$lastname)}}" id="name">
                                                </div>
                                                <div class="col-xs-12">
                                                        <input type="text" name="emailaddress" placeholder="Email Address" value="{{$email}}" id="emailaddress">
                                                </div>
                                                <div class="col-xs-12">
                                                        <input type="text" name="phonenumber" placeholder="Phone number" value="{{$phonenumber}}" id="phonenumber">
                                                </div>
                                        </div>

                                        <div class="row">
                                                <div class="col-xs-12">
                                                        <textarea cols="40" rows="3" name="message" id="showingmessage" placeholder="Notes..."></textarea> 
                                                </div>
                                        </div>

                    <div class="lds-ellipsis" id="viewingRequestLoader" style="position:absolute; @if( !empty($user->role) && $user->role == "AGENT") bottom:100px; @else bottom:56px; @endif right:46px;display:none">
                                                <div></div>
                                                <div></div>
                                                <div></div>
                                                <div></div>
                    </div>
                    <button class="listing__schedule--tour--send" id="sendViewingReq" type="submit">Book Viewing</button>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">Close</button>

                                </form>
                        </div>
                        
                        <div class="modal-footer"></div>
                </div>
        </div>
</div>
@endpush
@push('after-scripts')
<script>
                jQuery('.listing__schedule--tour--button button').click(function(evt){
                var thisForm = $(this).closest('form'); // to enable multiple instances of the form in a page.
                var scheduleReltorInput = jQuery("input[name='showing_realtor']:checked",thisForm).val();
                var schedulePreApprovedMortgageInput = jQuery("input[name='approved_check']:checked",thisForm).val();
                var preferredDate = jQuery("input[name='day_selector']:checked", thisForm).val();
                var preferredTime = jQuery("select[name='time_selector']", thisForm).find(":selected").val();
                
                
                jQuery('.listing__schedule--tour select,.listing__schedule--tour input').on('click check select change',function(){
                        jQuery('.listing__schedule--tour--errors',thisForm).hide();
                });
                
                var errflag = false;
                
                
                if(!jQuery('input[name="showing_realtor"]').is(':checked')){
                        jQuery('.listing__schedule--tour--errors-realtor',thisForm).show();
                        jQuery('.listing__schedule--tour--radio').on('check select click change','.realtorReqCheck',function(){
                                jQuery('.listing__schedule--tour--errors-realtor',thisForm).hide();
                        });
                        
                        // document.querySelector('input[name="showing_realtor"]').setCustomValidity('Required');
                        errflag = true;
                }

                if(!jQuery('input[name="approved_check"]').is(':checked')){
                        jQuery('.listing__schedule--tour--errors-pre-approved-mortgage',thisForm).show();
                        jQuery('.listing__schedule--tour--radio').on('check select click change','.pre-approved-mortgageReqCheck',function(){
                                jQuery('.listing__schedule--tour--errors-pre-approved-mortgage',thisForm).hide();
                        });
                        
                        // document.querySelector('input[name="showing_realtor"]').setCustomValidity('Required');
                        errflag = true;
                }
                
                if(errflag){
                        evt.preventDefault();
                        jQuery('#scheduleModal').modal('hide');
                return false;
        }

                jQuery('input#scheduleRealtor').val(scheduleReltorInput);
                jQuery('input#schedulePreApprovedMortgage').val(schedulePreApprovedMortgageInput);
                jQuery('input#preferredDate').val(preferredDate);
                jQuery('input#preferredTime').val(preferredTime);
        });

        jQuery('.showingReq_form').on('submit', function(e){
                e.preventDefault();
                jQuery("#send_showing_error", this).hide();

                var form = $(this);
                var data = getFormData(form);
                jQuery("#sendViewingReq",this).attr("disabled", true).addClass('inactive-red').text('Sending Request...');
                jQuery("#viewingRequestLoader",this).show();
                $.ajax({
                        type: "POST",
                        url: "{{route('api:request_showing')}}",
                        // The key needs to match your method's input parameter (case-sensitive).
                        data: JSON.stringify(data),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        success: function(data){

                                setTimeout( function(){ 
                                        jQuery("#sendViewingReq",form).removeClass('inactive-red');
                                        if(data.success){
                                                jQuery("#sendViewingReq", form).text('Request Sent! A member of our team will contact you');
                                        }else{
                                                if(data.message){
                                                        jQuery("#sendViewingReq", form).text(data.message);
                                                }else{
                                                        jQuery("#sendViewingReq", form).text('Something went wrong!');
                                                }
                                                jQuery("#sendViewingReq,.listing__schedule--tour--send",form).addClass('inactive-red');
                                        }
                                        jQuery("#sendingRequestLoader", form).hide();
                                        jQuery("#viewingRequestLoader", form).hide();
                                  }  , 1000 );
                                //jQuery(".showingReq_form .close").text("Back");
                                jQuery(".showingReq_form .scheduleApp").hide();
                                jQuery(".showingReq_form input").hide();
                                jQuery(".showingReq_form textarea").hide();
                                document.getElementById("showingReq_form").reset();
                        },
                        error: function(errMsg){
                                jQuery("#sendViewingReq", form).text( errMsg.message?errMsg.message:'Request Failed! ');
                                jQuery("#sendingRequestLoader", form).hide(); jQuery("#viewingRequestLoader", form).hide();
                        },
                });

                @if($user && $userIsPixiMember)
                /*var metadata = {
                        first_name: firstname,
                        last_name: lastname,
                        email: emailaddress,
                        phone: phone,
                        language: language,
                        working_with_realtor: working_with_realtor,
                        pre_approved_mortgage: pre_approved_mortgage,
                        prefered_date_1:prefered_date_1,
                        prefered_time_1: prefered_time_1,
                        prefered_date_2: prefered_date_2,
                        prefered_time_2: prefered_time_2,
                        message: message,
                        listing_id: '{{$listing->listingid}}'
                };*/
                console.log(data);
                @else
                @endif
        });
</script>
@endpush
<style>
.listing__schedule--tour--radio label {
    font-size: 14px;
    font-weight: 400;
    color: #484848;
    margin-right: 10px;
    margin-bottom: 0;
        border: none;
}
.listing__schedule--tour--realtor-header{
        margin-bottom: 0px;
        font-size: 14px;
        font-weight: 600;
}
.listing__schedule--tour--realtor{
        margin-bottom: 10px;
}
</style>
@endif
@endif