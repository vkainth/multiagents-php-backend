<style>
    .listing__schedule--tour--time--dropdown select{
        margin-bottom: 0px;
        color:#484848;
    }
</style>
<div class="listing-detail__details listing-detail--border">
	<div class="listing-detail__title"><h2>Mortgage</h2></div>
		<div class="listing__mortgage" id="mortgageCalculator-2">
			<div class="row">
                <div class="col-sm-6 col-xs-6 nopadding-left">
					<div class="listing__mortgage-box listing__mortgage-box-topleft">
						<label class="control-label" for="inputPropertyPrice">Property Price</label>
                        <input type="text" id="inputPropertyPrice" value="{{$listing->listprice}}" readonly class="form-control">
                    </div>
				</div>
				<div class="col-sm-6 col-xs-6 nopadding-right">
					<div class="listing__mortgage-box listing__mortgage-box-topright">
						<label class="control-label" for="inputRate">Interest Rate %<!--<a href="javascript:;" onclick="Intercom('showNewMessage', 'Looking to acquire the posted mortgage rate.');">Get Rate</a>--></label>
						@if($listing->listprice_2 > 1000000)
                        <input type="text" id="inputRate" value="3.94" class="form-control" onchange="getMortgageDetail()">
                        @else
                        <input type="text" id="inputRate" value="3.94" class="form-control" onchange="getMortgageDetail()">
                        @endif
					</div>
			    </div>
                <div class="col-sm-6 col-xs-6 nopadding-left">
                    <div class="listing__mortgage-box listing__mortgage-box-bottomleft">
                        <label class="control-label" for="inputDownPayment">Down Payment</label>
                        <select id="inputDownPayment" class="form-control" onchange="getMortgageDetail()">
                            @if($listing->listprice_2 < 1000000)
                            <option value="5" selected="selected">5%</option>
                            <option value="10">10%</option>
                            <option value="15">15%</option>
                            @endif
                            @if($listing->listprice_2 >= 1000000)
                            <option value="20" selected="selected">20%</option>
                            @else
                            <option value="20">20%</option>
                            @endif
                            <option value="30">30%</option>
                            <option value="40">40%</option>
                            <option value="50">50%</option>
                            <option value="60">60%</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-6 col-xs-6 nopadding-right">
                    <div class="listing__mortgage-box listing__mortgage-box-bottomright">
                        <label class="control-label" for="inputRentalincome">Monthly Rental Income</label>
                        <div class="input-rentalincome">
                            <input type="text" min="0" id="inputRentalincome" class="form-control" onchange="getMortgageDetail()">
                        </div>
                    </div>
                </div>
			</div>
		</div>
    <br/><br/>
    <div class="listing-detail__table">
	    <table class="table table-striped">
			<tbody>
				<tr>
					<td id="downpayment_title_1">Downpayment</td>
					<td id="downpayment_title_2" style="display:none;">Minimum Downpayment <span style="font-size:10px;">(5% on first $500,000 and 10% on the rest)</span></td>
					<td id="result_1"></td>
				</tr>
                <tr>
					<td>Rental Income</td>
					<td id="result_6"></td>
				</tr>
                <tr>
					<td><strong>Monthly Mortgage Payment</strong></td>
					<td ><strong id="result_2"></strong></td>
				</tr>
                <tr>
					<td>Effective Monthly Mortgage Payment</td>
					<td id="result_3"></td>
				</tr>
                <tr>
					<td>Qualification Monthly Payment</td>
					<td id="result_10"></td>
				</tr>
                <tr>
					<td>Interest Rate</td>
					<td id="result_7"></td>
				</tr>
                <tr>
					<td>Qualification Interest Rate</td>
					<td id="result_9"></td>
				</tr>
                <tr>
					<td>Income Required</td>
					<td id="result_8"></td>
				</tr>
                <tr>
					<td>Qualification Annual Income Required</td>
					<td id="result_11"></td>
				</tr>
                <tr>
					<td>CMHC Fees</td>
					<td id="result_4"></td>
				</tr>
                <tr>
					<td>Amortization Period</td>
					<td id="result_5"></td>
				</tr>
            </tbody>
        </table>
        <p>Mortgages can be confusing.  Got Questions? Call us <a href="tel:+16043303784">604-330-3784</a></p>
    </div>
</div>
<script>
    function calculateMortgage(propertyPrice, downpaymentPercentage, interestRate, rentalIncome, gdsRatio) {
    // Step 1: Calculate Downpayment
    let totalDownpayment;
    document.getElementById("downpayment_title_2").style.display = 'none';
        document.getElementById("downpayment_title_1").style.display = 'block';
    if (downpaymentPercentage === 'minimum') {
        const firstSegment = 500000;
        const downpaymentFirstSegment = firstSegment * 0.05;
        totalDownpayment = downpaymentFirstSegment;
    } else if(downpaymentPercentage === 0.05 && propertyPrice > 500000){
        const firstSegment = 500000;
        const downpaymentFirstSegment = firstSegment * 0.05;
        const secondSegment = propertyPrice - firstSegment;
        const downpaymentSecondSegment = secondSegment * 0.10;
        totalDownpayment = downpaymentFirstSegment + downpaymentSecondSegment;
        document.getElementById("downpayment_title_2").style.display = 'block';
        document.getElementById("downpayment_title_1").style.display = 'none';
    } 
    else
    {
        totalDownpayment = propertyPrice * downpaymentPercentage;
    }

    // Determine the applicable CMHC rate
    let cmhcRate = 0;
    if (downpaymentPercentage === 0.05) {
        cmhcRate = 0.04; // 4.00% for 5% down payment
    } else if (downpaymentPercentage === 0.10) {
        cmhcRate = 0.031; // 3.10% for 10% down payment
    } else if (downpaymentPercentage === 0.15) {
        cmhcRate = 0.028; // 2.80% for 15% down payment
    } else if (downpaymentPercentage === 0.20) {
        cmhcRate = 0; // No CMHC required for 20% down payment
    }

    // Determine if CMHC insurance is applicable and calculate CMHC fees
    let cmhcFees = 0;
    let amortizationYears = 25;
    if (propertyPrice > 1000000) {
        //if (totalDownpayment / propertyPrice >= 0.25) {
            amortizationYears = 30;
        //}
    } else if (propertyPrice <= 999999) {
        cmhcFees = (propertyPrice - totalDownpayment) * cmhcRate;
        if(downpaymentPercentage >= 0.20){
            amortizationYears = 30;
        }
        else{
            amortizationYears = 25;
        }
    }

    // Step 2: Calculate Mortgage Amount
    const principalLoanAmount = propertyPrice - totalDownpayment + cmhcFees;

    // Step 3: Calculate Monthly Mortgage Payment
    const monthlyInterestRate = interestRate / 12;
    const numberOfPayments = amortizationYears * 12;
    const monthlyPayment = (principalLoanAmount * monthlyInterestRate * Math.pow(1 + monthlyInterestRate, numberOfPayments)) / (Math.pow(1 + monthlyInterestRate, numberOfPayments) - 1);

    // Step 4: Adjust for Rental Income
    const effectiveMonthlyPayment = monthlyPayment - rentalIncome;

    // Step 5: Calculate Required Income to Qualify (Stress Test Rate)
    const qualificationInterestRate = interestRate + 0.02;
    const qualificationMonthlyRate = qualificationInterestRate / 12;
    const qualificationNumberOfPayments = 25 * 12; // Using 25 years for qualification calculation
    const qualificationMonthlyPayment = (principalLoanAmount * qualificationMonthlyRate * Math.pow(1 + qualificationMonthlyRate, qualificationNumberOfPayments)) / (Math.pow(1 + qualificationMonthlyRate, qualificationNumberOfPayments) - 1);
    const qualificationAnnualIncomeRequired = (qualificationMonthlyPayment * 12) / gdsRatio;

    // Step 6: Calculate General Income Requirement
    const annualIncomeRequired = (effectiveMonthlyPayment * 12) / gdsRatio;

    return {
        totalDownpayment: totalDownpayment,
        monthlyPayment: monthlyPayment,
        effectiveMonthlyPayment: effectiveMonthlyPayment,
        cmhcFees: cmhcFees,
        amortizationYears: amortizationYears,
        rentalIncome: rentalIncome,
        interestRate: interestRate,
        qualificationInterestRate: qualificationInterestRate,
        qualificationMonthlyPayment: qualificationMonthlyPayment,
        qualificationAnnualIncomeRequired: qualificationAnnualIncomeRequired,
        annualIncomeRequired: annualIncomeRequired
    };
}

function displayResults(result) {
    $("#result_1").text(result.totalDownpayment.toLocaleString('en-US', { style: 'currency', currency: 'USD' }));
    $("#result_2").text(result.monthlyPayment.toLocaleString('en-US', { style: 'currency', currency: 'USD' }));
    $("#result_3").text(result.effectiveMonthlyPayment.toLocaleString('en-US', { style: 'currency', currency: 'USD' }));
    $("#result_4").text(result.cmhcFees.toLocaleString('en-US', { style: 'currency', currency: 'USD' }));
    $("#result_5").text(result.amortizationYears + " years");
    $("#result_6").text(result.rentalIncome.toLocaleString('en-US', { style: 'currency', currency: 'USD' }));
    $("#result_7").text((result.interestRate * 100).toFixed(2)+"%");
    $("#result_8").text(result.annualIncomeRequired.toLocaleString('en-US', { style: 'currency', currency: 'USD' }));
    $("#result_9").text((result.qualificationInterestRate * 100).toFixed(2)+"%");
    $("#result_10").text(result.qualificationMonthlyPayment.toLocaleString('en-US', { style: 'currency', currency: 'USD' }));
    $("#result_11").text(result.qualificationAnnualIncomeRequired.toLocaleString('en-US', { style: 'currency', currency: 'USD' }));
    // console.log(`Downpayment: $${result.totalDownpayment.toFixed(2)}`);
    // console.log(`Monthly Mortgage Payment: $${result.monthlyPayment.toFixed(2)}`);
    // console.log(`Effective Monthly Mortgage Payment: $${result.effectiveMonthlyPayment.toFixed(2)}`);
    // console.log(`CMHC Fees: $${result.cmhcFees.toFixed(2)}`);
    // console.log(`Amortization Period: ${result.amortizationYears} years`);
    // console.log(`Rental Income: $${result.rentalIncome.toFixed(2)}`);
    // console.log(`Interest Rate: ${(result.interestRate * 100).toFixed(2)}%`);
    // console.log(`Income Required: $${result.annualIncomeRequired.toFixed(2)}`);
    // console.log(`Qualification Interest Rate: ${(result.qualificationInterestRate * 100).toFixed(2)}%`);
    // console.log(`Qualification Monthly Payment: $${result.qualificationMonthlyPayment.toFixed(2)}`);
    // console.log(`Qualification Annual Income Required: $${result.qualificationAnnualIncomeRequired.toFixed(2)}`);
    // console.log('\n');
}
    function getMortgageDetail(){
        var propertyPrice = {{$listing->listprice_2}}; // Change this value for different property prices
        var rentalIncome = document.getElementById("inputRentalincome").value?Number(document.getElementById("inputRentalincome").value): 0;
        var gdsRatio = 0.39;
        var downpayment = document.getElementById("inputDownPayment").value;
        var downpaymentPercentage = Number(downpayment)/100;
        var interest = document.getElementById("inputRate").value;
        var interestRate = Number(interest)/100;
        var results = calculateMortgage(propertyPrice, downpaymentPercentage, interestRate, rentalIncome, gdsRatio);
        displayResults(results);
    }

    document.addEventListener("DOMContentLoaded", function() {
        getMortgageDetail();
    });
    
// Input values
// const propertyPrice = 500000; // Change this value for different property prices
// const rentalIncome = 0;
// const gdsRatio = 0.39;

// Determine the interest rate based on the property price
// const interestRate = propertyPrice > 1000000 ? 0.0554 : 0.0504;

// // Display results based on the property price
// if (propertyPrice <= 999999) {
//     // Calculate for 5%, 10%, and 15% downpayments
//     const downpayment5Result = calculateMortgage(propertyPrice, 0.05, interestRate, rentalIncome, gdsRatio);
//     const downpayment10Result = calculateMortgage(propertyPrice, 0.10, interestRate, rentalIncome, gdsRatio);
//     const downpayment15Result = calculateMortgage(propertyPrice, 0.15, interestRate, rentalIncome, gdsRatio);

//     displayResults(downpayment5Result, '5%');
//     displayResults(downpayment10Result, '10%');
//     displayResults(downpayment15Result, '15%');
// } else {
//     // Calculate for 20%, 25%, and 30% downpayments
//     const downpayment20Result = calculateMortgage(propertyPrice, 0.20, interestRate, rentalIncome, gdsRatio);
//     const downpayment25Result = calculateMortgage(propertyPrice, 0.25, interestRate, rentalIncome, gdsRatio);
//     const downpayment30Result = calculateMortgage(propertyPrice, 0.30, interestRate, rentalIncome, gdsRatio);

//     displayResults(downpayment20Result, '20%');
//     displayResults(downpayment25Result, '25%');
//     displayResults(downpayment30Result, '30%');
// }

</script>