@extends('frontend.layouts.default')
@section('title')
    Terms and Conditions | Fisherly
@endsection
@section('content')
    @include('frontend.includes.header')


    <div class="main" role="main" style="text-align: justify">
        <div class="main" role="main" style="text-align: justify">
            <div class="container">
                <div class="legal__item" style="text-align: justify">
                    <h1>Terms & Conditions</h1>
                    <div class="legal__updated">LAST UPDATED: February 8, 2019</div>
                    {{-- <div class="legal__paragraph">
                        <ol type="a">
                        <li>the Registrant has received, read and understood the brochure published by the British Columbia Real Estate Association entitled “Privacy Notice and Consent” (this document may be linked);</li>
                        <li>the Registrant acknowledges entering into a lawful REALTOR®/ consumer or REALTOR®/client relationship with the Member;</li>
                        <li>all data obtained from the MLS® VOW is intended for and may only be used for the Registrant’s personal, non-commercial use;</li>
                        <li>the Registrant has a bona fide interest in the purchase, sale or lease of real estate of the type being offered through the MLS® VOW;</li>
                        <li>the Registrant will not himself, and will not permit or assist others to, directly or indirectly:
                            <ol type="i">
                           <li>copy, redistribute or retransmit any of the MLS® VOW Data or information provided;</li>
                          <li>display, post, disseminate, distribute, publish, broadcast, transfer, sell or sublicense any of the MLS® VOW Data to another person.</li>
                           <li>engage in Scraping (including “screen scraping” and “database scraping”), “data mining” or any other activity intended to collect, store, re-organize, summarize or manipulate any MLS® VOW Data or any related data;</li>
                        </ol>
                            <li>the Registrant acknowledges the Board’s ownership of, and the validity of the Board’s proprietary rights and copyright in the MLS® VOW Data, and listing information; and</li>
                        <li>the Registrant expressly authorizes the Board or their duly authorized representatives, to access the MLS® VOW and Registrant’s information provided to the MLS® VOW Participant, for the purposes of verifying compliance with and pursuing enforcement of the Terms of Use and all applicable rules, regulations, bylaws, policies, and laws.</li>
                        </ol>
                    </div> --}}
                    <div class="legal__paragraph">
                        <h2>Terms of Use Agreement</h2>
                        <p>By accessing any of the websites or mobile applications operated by the agent you agree to be bound by all of the TERMS & CONDITIONS and PRIVACY POLICY and agree that these terms constitute a binding contract between you and the agent.</p>
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
                        <p>We makes no representations about the suitability of the data, information, or graphics published on this site. Everything on this site is provided "As Is" and "As Available" without warranty of any kind including all implied warranties and conditions of merchantability, fitness for a particular purpose, title and non-infringement. Neither REALTOR®, Pixilink, Fisherly nor any of its members, directors, officer, shareholders or affiliates shall be liable for any direct, incidental, consequential, indirect or punitive damages arising out of your access to or use of this site.</p>
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
                        </ul>
                    </div>


                    <div class="legal__paragraph">
                        <h2>Commercial Electronic Messages ("CEMs")</h2>
                        <p>The REALTOR® will only sends CEMs, such as emails, in accordance with Canada's Anti-Spam Legislation ("CASL"). </p>
                    </div>

                    <div class="legal__paragraph">
                        <h2>Third Party Websites</h2>
                        <p>The Website/App may contain links from other third party websites and all such websites are independent. REALTOR® has no control over these third party websites and assumes no responsibility or obligations for such third party websites. The provision of such links does not constitute any endorsement of such linked websites, their content or information appearing on the Website/App.</p>
                    </div>



                    <div class="legal__paragraph">
                        <h2>Jurisdiction</h2>
                        <p>The Website/App can be accessed from all provinces and territories of Canada, as well as from other countries around the world. As each of these jurisdictions has laws that may differ from those of the Province of British Columbia, by accessing the Website/App or Associated Services, you agree that all matters relating to access to, or use of, the Website/App or Associated Services, or any other hyperlinked website shall be governed by the laws of the Province of British Columbia and the federal laws of Canada as applicable and notwithstanding conflicts of law. You also agree and hereby submit to the exclusive jurisdiction and venue of the courts of the Province of British Columbia and acknowledge and do so voluntarily.</p>
                    </div>

@endsection
