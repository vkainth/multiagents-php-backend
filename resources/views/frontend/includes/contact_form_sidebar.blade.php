@php
$auth_user = null;
if(Auth::user()){
    $auth_user = Auth::user();
}
@endphp
<div class="listing__sidebar-contact">
    <h3>Contact</h3>
    <form id="contactus_form" class="listing__contact-form" autocomplete="off" method="post" action="">
        <div class="row">
            <div class="col-xs-12">
                <input type="hidden" name="listingid" value="{{$listing->listingid}}">
                <input type="text" name="fullname" placeholder="Name" value="{{$auth_user?$auth_user->first:''}} {{$auth_user?$auth_user->last:''}}" id="full-name-contact">
            </div>
            <div class="col-xs-12">
                <input type="text" name="emailaddress" placeholder="Email Address" value="{{$auth_user?$auth_user->email:''}}" id="email-address-contact">
            </div>
            <div class="col-xs-12">
                <input type="text" name="phonenumber" placeholder="Phone number" value="{{$auth_user?$auth_user->phone:''}}" id="phone-number-contact">
            </div>
            <div class="col-xs-12">
                <textarea cols="40" rows="3" name="message" id="contactgmessage" placeholder="Message...">I would like to see {{$listing->streetaddress}}</textarea> 
            </div>

            <div class="col-xs-12">
                <div class="row">
                    <div class="col-md-8 col-sm-8 col-xs-12 label--head">Are you working with a REALTOR®?</div>
                    <div class="col-md-2 col-sm-2 col-xs-2">
                        <label>Yes</label>
                        <input type="radio" name="agent-check-contactus" value="Yes" id="agentcheck1_contactus">
                    </div>
                    <div class="col-md-2 col-sm-2 col-xs-2">
                        <label>No</label>
                        <input type="radio" name="agent-check-contactus" value="No" id="agentcheck2_contactus">
                    </div>
                </div>
            </div>

        </div>
        <button class="listing__contact-form__button" type="submit" id="contactsubmit">Submit</button>
    </form>
</div>
