@if(!empty($listing->status) && $listing->status=='Active' && 1==0)
{{-- @if(!empty($user->email) && substr($user->email,-12)=='pixilink.com')
<div class="listing__schedule--tour" style="/*box-shadow: rgba(0,0,0,.4) 0 0 8px;*/">
@else
<div class="listing__schedule--tour" style="/*box-shadow: rgba(0,0,0,.4) 0 0 8px;*/display: none;">
@endif
 --}}
@auth
<div class="listing__schedule--tour" style="/*box-shadow: rgba(0,0,0,.4) 0 0 8px;*/">
	<h3>Schedule Viewing</h3>
	<form id="showing_form" class="listing-detail__showing showing_form" autocomplete="off" method="post" action="">
		@csrf
		<div class="listing__schedule--tour--calendar-wrap clearfix">
			<div class="swiper-container">
				<div class="swiper-wrapper">
					@php
					$startDay = Carbon\Carbon::now()->addDay();
					$endDay = Carbon\Carbon::now()->addDays(8);
					@endphp
					@while($startDay <= $endDay)
					<div class="swiper-slide">
						<div class="showing__checkbox--day">
							<label class="checkbox">
								<input type="radio" name="showing_date" class="showing-day__checked" value="{{$startDay->format('Y-m-d')}}">
								<div>
									<span class="listing__schedule--tour-weekday">{{$startDay->format('l')}}</span>
									<span class="listing__schedule--tour-day">{{$startDay->format('d')}}</span>
									<span class="listing__schedule--tour-month">{{$startDay->format('M')}}</span>
								</div>
							</label>
						</div>
					</div>
					@php
					$startDay->addDay();
					@endphp
					@endwhile   
				</div>
			</div>  

			<div class="swiper-button-prev" style="display:">
				<!--<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27 44"><path d="M0,22L22,0l2.1,2.1L4.2,22l19.9,19.9L22,44L0,22L0,22L0,22z" fill="#333"></svg>-->
				<svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="36.000000pt" height="37.000000pt" viewBox="0 0 36.000000 37.000000" preserveAspectRatio="xMidYMid meet">
					<g transform="translate(0.000000,37.000000) scale(0.100000,-0.100000)" fill="#454545" stroke="none">
						<path d="M162 217 l-32 -33 35 -34 c19 -18 37 -31 40 -29 3 3 -9 18 -25 34 l-30 29 32 33 c18 18 27 33 22 33 -6 0 -24 -15 -42 -33z"/>
					</g>
				</svg>
			</div>
			<div class="swiper-button-next" style="display:">
				<!--<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27 44"><path d="M27,22L27,22L5,44l-2.1-2.1L22.8,22L2.9,2.1L5,0L27,22L27,22z" fill="#333"></svg>-->
				<svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="36.000000pt" height="37.000000pt" viewBox="0 0 36.000000 37.000000" preserveAspectRatio="xMidYMid meet">
					<g transform="translate(0.000000,37.000000) scale(0.100000,-0.100000)" fill="#454545" stroke="none">
						<path d="M178 217 l32 -33 -30 -29 c-16 -16 -28 -31 -25 -34 3 -2 21 11 40 29 l35 34 -32 33 c-18 18 -36 33 -42 33 -5 0 4 -15 22 -33z"/>
					</g>
				</svg>
			</div>
		</div>

		<div class="listing__schedule--tour--time--dropdown">
			<select required="">
				<option value="">Choose a Time...</option>
				<option value="09:00">09:00am</option>
				<option value="09:30">09:30am</option>
				<option value="10:00">10:00am</option>
				<option value="10:30">10:30am</option>
				<option value="11:00">11:00am</option>
				<option value="11:30">11:30am</option>
				<option value="12:00">12:00pm</option>
				<option value="12:30">12:30pm</option>
				<option value="13:00">1:00pm</option>
				<option value="13:30">1:30pm</option>
				<option value="14:00">2:00pm</option>
				<option value="14:30">2:30pm</option>
				<option value="15:00">3:00pm</option>
				<option value="15:30">3:30pm</option>
				<option value="16:00">4:00pm</option>
				<option value="16:30">4:30pm</option>
				<option value="17:00">5:00pm</option>
				<option value="17:30">5:30pm</option>
				<option value="18:00">6:00pm</option>
				<option value="18:30">6:30pm</option>
				<option value="19:00">7:00pm</option>
				<option value="19:30">7:30pm</option>
				<option value="20:00">8:00pm</option>
				<option value="20:30">8:30pm</option>
			</select>
		</div>

		<div class="listing__schedule--tour--errors" style="display:none; color:#EE4223;padding-bottom:1em;">
			*Please select date and time-slots for booking!
		</div>

		<div class="listing__schedule--tour--realtor">
			<div class="listing__schedule--tour--realtor-header">Are you working with a realtor?</div>
			<div class="listing__schedule--tour--radio" id="workWithRealtorReq">
				<label>
					<input type="radio" name="showing_realtor" value="Yes" class="realtorReqCheck"><span>Yes</span>
				</label>
				<label>
					<input type="radio" name="showing_realtor" value="No" class="realtorReqCheck"><span>No</span>
				</label>
				<span class="listing__schedule--tour--errors-realtor" style="display:none; color:#EE4223;">*Required!</span>
			</div>
		</div>


		<div class="listing__schedule--tour--pre-approved-mortgage">
			<div class="listing__schedule--tour--pre-approved-mortgage-header">Are you pre-approved for mortgage?</div>
			<div class="listing__schedule--tour--radio" id="workWithRealtorReq">
				<label>
					<input type="radio" name="approved_check" value="Yes" class="pre-approved-mortgageReqCheck"><span>Yes</span>
				</label>
				<label>
					<input type="radio" name="approved_check" value="No" class="pre-approved-mortgageReqCheck"><span>No</span>
				</label>
				<span class="listing__schedule--tour--errors-pre-approved-mortgage" style="display:none; color:#EE4223;">*Required!</span>
			</div>
		</div>
		<br>

		<div class="listing__schedule--tour--button">
			<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#scheduleModal">Book An Appointment</button>
		</div>

	</form>
</div>
@endauth
@endif


@if(!empty($listing->status) && $listing->status=='Active')
@if(Auth::user() || 1==1)
<div class="listing__schedule--tour" style="/*box-shadow: rgba(0,0,0,.4) 0 0 8px;*/">
	<!--<h3>Schedule Viewing</h3>-->
	<form id="showing_form" class="listing-detail__showing showing_form" autocomplete="off" method="post" action="">
		@csrf
		<div class="listing__schedule--tour--realtor">
			<div class="listing__schedule--tour--realtor-header">Preferred Day</div>
			<div class="listing__schedule--tour--radio">
				<label><input type="radio" name="day_selector" value="weekdays"><span>Weekdays</span></label>
				<label><input type="radio" name="day_selector" value="weekends"><span>Weekends</span></label>
				<span class="listing__schedule--tour--errors-day" style="display:none; color:#EE4223;">*Required!</span>
			</div>
		</div>
		<div class="listing__schedule--tour--realtor">
			<div class="listing__schedule--tour--realtor-header"><label for="select-slots-time_selector">Preferred Time</label></div>
			<div class="listing__schedule--tour--time--dropdown">
			<select required="" name="time_selector" id="select-slots-time_selector">
				<option value="">Choose a Time...</option>
				<option value="9 am to 12 pm">9 am to 12 pm</option>
				<option value="12 pm to 4 pm">12 pm to 4 pm</option>
				<option value="4 pm to 6 pm">4 pm to 6 pm</option>
			</select>
		</div>
		<span class="listing__schedule--tour--errors-time" style="display:none; color:#EE4223;">*Required!</span>
			<!--<div class="listing__schedule--tour--radio">-->
			<!--	<label><input type="radio" name="time_selector" value="9 am to 12 pm"><span>9 am to 12 pm</span></label>-->
			<!--	<label><input type="radio" name="time_selector" value="12 pm to 4 pm"><span>12 pm to 4 pm</span></label>-->
			<!--	<label><input type="radio" name="time_selector" value="4 pm to 6 pm"><span>4 pm to 6 pm</span></label>-->
			<!--	<span class="listing__schedule--tour--errors-time" style="display:none; color:#EE4223;">*Required!</span>-->
			<!--</div>-->
		</div>
		<div class="listing__schedule--tour--errors" style="display:none; color:#EE4223;padding-bottom:1em;">
			*Please select date and time-slots for booking!
		</div>

		<div class="listing__schedule--tour--realtor">
			<div class="listing__schedule--tour--realtor-header">Are you working with a realtor?</div>
			<div class="listing__schedule--tour--radio" id="workWithRealtorReq">
				<label>
					<input type="radio" name="showing_realtor" value="Yes" class="realtorReqCheck"><span>Yes</span>
				</label>
				<label>
					<input type="radio" name="showing_realtor" value="No" class="realtorReqCheck"><span>No</span>
				</label>
				<span class="listing__schedule--tour--errors-realtor" style="display:none; color:#EE4223;">*Required!</span>
			</div>
		</div>


		<div class="listing__schedule--tour--pre-approved-mortgage">
			<div class="listing__schedule--tour--pre-approved-mortgage-header" style="font-weight:600">Are you pre-approved for mortgage?</div>
			<div class="listing__schedule--tour--radio" id="workWithRealtorReq">
				<label>
					<input type="radio" name="approved_check" value="Yes" class="pre-approved-mortgageReqCheck"><span>Yes</span>
				</label>
				<label>
					<input type="radio" name="approved_check" value="No" class="pre-approved-mortgageReqCheck"><span>No</span>
				</label>
				<span class="listing__schedule--tour--errors-pre-approved-mortgage" style="display:none; color:#EE4223;">*Required!</span>
			</div>
		</div>
		<br>

		<div class="listing__schedule--tour--button">
			<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#scheduleModal">Request A Showing</button>
		</div>

	</form>
</div>
@endif
@endif