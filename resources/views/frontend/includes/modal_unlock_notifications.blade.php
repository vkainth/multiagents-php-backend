<div class="modal" id="unlockAdtnlNotifctnsModal" tabindex="-1" role="dialog" aria-labelledby="unlockAdtnlNotifctnsModalLabel" aria-hidden="true" data-backdrop="true" style="margin-top:60px;">
    <div class="modal-dialog modal-sm" role="document" style="z-index:105;">
        <div class="modal-content">
            <form id="formBcchTAdvsrCall9342k4H9" class="formAdvsrCall" action="#!" onsubmit="return false;">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title" class="text-center">Schedule a Call</h3>
                    {{-- <p id="unlockAdtnlNotifctns_modeltitle" class="text-center">Please choose a preferable date and time for one of our team members to contact you:</p> --}}
                </div>
                
                <div class="modal-body text-center">
                    <div class="text-warning alert alert-danger">Maximum number of notifications reached.  To unlock unlimited notifications schedule a call with a Hani & Les | BC Condos And Homes advisor.</div>
                    <div class="teamAgentCall--calendar-wrap clearfix">
                        <div class="vvswiper-container">
                            <div class="vvswiper-wrapper" style="display: inline-flex; flex-direction: column; align-items: flex-start;">
                                @for($theDay=Carbon\Carbon::now()->addDay(),$endDay = Carbon\Carbon::now()->addDays(5);$theDay<=$endDay;$theDay->addDay())
                                <div class="vvswiper-slide">
                                    <label class="">
                                        <input type="radio" name="advisorCall_date" class="advisorCall_date" value="{{$theDay->format('Y-m-d')}}">
                                        <div style="display: inline-block;">
                                            <span class="teamAgentCall-weekday">{{$theDay->format('l')}}</span>
                                            <span class="teamAgentCall-day">{{$theDay->format('d')}}</span>
                                            <span class="teamAgentCall-month">{{$theDay->format('M')}}</span>
                                        </div>
                                    </label>
                                </div>
                                @endfor
                            </div>
                        </div>  

                        <div class="teamAgentCall--time--dropdown" style="margin-top:10px">
                            <select required="" name="advisorCall_time" class="form-control">
                                <option value="">Choose a Time...</option>
                                @for($_slctTime = Carbon\Carbon::parse('09:00:00'), $_tillTime=Carbon\Carbon::parse('20:30:00');$_slctTime<=$_tillTime; $_slctTime->addMinutes(30) )
                                <option value="{{$_slctTime->format('H:i')}}">{{$_slctTime->format('h:i A')}}</option>
                                @endfor
                            </select>
                        </div>

                    </div>

                </div>
            
                <div class="modal-footer">
                    
                    <div class="unlkNtfdateTimeAlert alert alert-danger text-center" style="display:none;">* Please select Date and Time!</div>

                    <button class="btn btn-block btn-primary" for="formBcchTAdvsrCall9342k4H9" onclick="">Schedule Call</button>

                </div>

            </form>
        </div>
    </div>
</div>

<script>
@push('document-ready-javascript') 
try{
    (function(){
        var fr = jQuery('#formBcchTAdvsrCall9342k4H9');
        jQuery(fr).on('submit',function(){
            var f=jQuery(this), d=$(f).find('input:checked').val(),t=$(f).find('select').val();
            if(t==''||!d||d==''){ $(f).find('select').focus(); $(f).find('.unlkNtfdateTimeAlert').show(); return false; }else{ f.find('.unlkNtfdateTimeAlert').hide()}
        })
    })();
}catch(unlkEr){}
@endpush
</script>
