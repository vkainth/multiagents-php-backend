@extends('frontend.layouts.default')
@section('title')
    Terms and Conditions | Hani & Les | BC Condos And Homes
@endsection
@section('content')
@include('frontend.includes.header')


	<!--<div class="main" role="main" style="text-align: justify">-->
	<div class="main" role="main" style="text-align: justify; ">
		<div class="container">
			<div class="legal__item" style="margin-top:90px;">
				<h1>Terms & Conditions</h1>
				<div class="legal__updated">LAST UPDATED: March 9, 2021</div>


				<div class="legal__paragraph">
					<h2>Terms of Use Agreement</h2>
					<p>By accessing any of the websites or mobile applications operated by the agent you agree to be bound by all of the TERMS & CONDITIONS and PRIVACY POLICY and agree that these terms constitute a binding contract between you and Hani & Les | BC Condos And Homes.</p>
				</div>

				<div class="legal__paragraph">
					<h2>Copyright</h2>
					<p>Property listings and other data available on this site are intended for private, non-commercial use by individuals. Any commercial use of the listings or data in whole or in part, directly or indirectly, is specifically forbidden except with the prior written authority of the owner of the copyright.</p>
					<p>The users may, subject to the Terms and Conditions, print or otherwise save individual pages for private use. However, property listings and/or data may not be modified or altered in any respect, merged with other data or published in any form, in whole or in part. The prohibited uses include "screen scraping," "database scraping," and any other activities intended to collect, store, reorganize or manipulate or publish data on the pages produced by, or displayed by this website or its associated or affiliated websites.</p>
				    <p>REALTOR® is a certification mark owned by REALTOR® Canada Inc., a corporation owned by the National Association of REALTORS® and CREA.</p>
				    <p>Multiple Listing Service® is a registered certification mark owned by CREA and used to identify real estate services provided by brokers and salespersons who are members of CREA.</p>
				</div>

				<div class="legal__paragraph">
					<h2>Disclaimer</h2>
					<p>We make no representations about the suitability of the data, information, or graphics published on this site. Everything on this site is provided "As Is" and "As Available" without warranty of any kind including all implied warranties and conditions of merchantability, fitness for a particular purpose, title and non-infringement. Neither REALTOR®, Pixilink, Hani & Les | BC Condos And Homes nor any of its members, directors, officers, shareholders or affiliates shall be liable for any direct, incidental, consequential, indirect or punitive damages arising out of your access to or use of this site.</p>
				</div>

                <div class="legal__paragraph">
                    <h2>Privacy Notice and Consent</h2>
                    <p>In accordance with the Rules of Cooperation of the CADREB, FVREB, and REBGV, and in conjunction with Privacy Policy, the User acknowledges understanding of and agreement with the following:</p>
					<ul>
						<li>The Registrant has received, read and understood the brochure published by the British Columbia Real Estate Association entitled “Privacy Notice and Consent”;</li>
						<li>all data obtained from the MLS® VOW is intended for and may only be used for the User’s personal, non-commercial use;</li>
						<li>the Registrant has a bona fide interest in the purchase, sale or lease of real estate of the type being offered through the MLS® VOW;</li>
						<li>the Registrant will not himself, and will not permit or assist others to, directly or indirectly:
                            <ul>
                                <li>copy, redistribute or retransmit any of the MLS® VOW Data or information provided;</li>
                                <li>display, post, disseminate, distribute, publish, broadcast, transfer, sell or sublicense any of the MLS® VOW Data to another person.</li>
                                <li>engage in Scraping (including “screen scraping” and “database scraping”), “data mining” or any other activity intended to collect, store, re-organize, summarize or manipulate any MLS® VOW Data or any related data;</li>
                            </ul>
                        </li>
                        <li>the Registrant acknowledges the Board’s ownership of, and the validity of the Board’s proprietary rights and copyright in the MLS® VOW Data, and listing information; and</li>
                        <li>the Registrant expressly authorizes the Board or their duly authorized representatives, to access the MLS® VOW and User’s information provided to the MLS® VOW Participant, for the purposes of verifying compliance with and pursuing enforcement of the Terms of Use and all applicable rules, regulations, bylaws, policies, and laws.</li>
                        <li>Acknowledge and understand that the Terms of Use do not create an agency relationship and do not impose a financial obligation on the Registrant or create any representation agreement between the Registrant and the Participant;</li>
                        <li>Acknowledge and enter into a lawful REALTOR®/ consumer or REALTOR®/client relationship with the Participant, including, where necessary, completion of any applicable agency, non-agency, and other disclosure obligations, and execution of any required agreements;</li>
                        <li>Understand that information on this site is deemed to be valid but is not guaranteed. It is the responsibility of the registrants to confirm all information on their own.</li>
                    </ul>
                </div>
                
                <div class="legal__paragraph">
					<h2>Communication</h2>
					<p>The Registrant expressly authorizes the Board, their duly authorized representatives or the REALTOR®, to call the registrant by the phone provided.</p>
				</div>

				<div class="legal__paragraph">
					<h2>Commercial Electronic Messages ("CEMs")</h2>
					<p>The REALTOR® will only sends CEMs, such as emails, in accordance with Canada's Anti-Spam Legislation ("CASL").</p>
				</div>

				<div class="legal__paragraph">
					<h2>Third Party Websites</h2>
					<p>The Website/App may contain links from other third party websites and all such websites are independent. REALTOR® has no control over these third party websites and assumes no responsibility or obligations for such third party websites. The provision of such links does not constitute any endorsement of such linked websites, their content or information appearing on the Website/App.</p>
				</div>



				<div class="legal__paragraph">
					<h2>Jurisdiction</h2>
					<p>The Website/App can be accessed from all provinces and territories of Canada, as well as from other countries around the world. As each of these jurisdictions has laws that may differ from those of the Province of British Columbia, by accessing the Website/App or Associated Services, you agree that all matters relating to access to, or use of, the Website/App or Associated Services, or any other hyperlinked website shall be governed by the laws of the Province of British Columbia and the federal laws of Canada as applicable and notwithstanding conflicts of law. You also agree and hereby submit to the exclusive jurisdiction and venue of the courts of the Province of British Columbia and acknowledge and do so voluntarily.</p>
                    <p>The advertising on this website is provided on behalf of the Hani & Les | BC Condos And Homes - Re/Max Crest Realty, 300 - 1195 W Broadway, Vancouver, BC</p>				
				</div>

			</div>

		</div>
	</div>

	@include('frontend.includes.footer_links')

	<footer>
    	<div class="container">
    	    <div class="footer__information">
    	       	<p><a href="/terms-and-conditions" target="_blank">Terms & Conditions</a> &#183; <a href="/privacy-policy" target="_blank">privacy 	policy</a> {{--| a project by &copy; Pixilink Solutions {{date('Y')}}--}}</p>
            	<p><!--<span>powered by</span>--><img src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.svg" alt="Hani & Les | BC Condos And Homes Logo Footer" loading="lazy" alt="Hani & Les | BC Condos And Homes" /></p>
       		</div>
       		<div class="footer__contact-info">
       			<p class="footer__address">300 - 1195 W Broadway<br>Vancouver, BC V6H 3X5</p>
       			<div class="footer__contact">
       				Phone: <a href="tel:6042657975">604-265-7975</a><br>
       				Email: <a href="mailto:info@bccondosandhomes.com">Info@bccondosandhomes.com</a>
       			</div>
       		</div>
    	</div>
	</footer>

@endsection
