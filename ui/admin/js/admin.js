(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	$(function () {
		$(document).on('click', '.qpp_notice .notice-dismiss', function( event ) {
			let data = {
				action : 'qpp_dismiss_notice',
				id: $(this).closest('div').attr('id')
			};

			$.post(ajaxurl, data, function (response) {
				console.log(response, 'DONE!');
			});
		});
	})
	$(document).ready(function () {
		// Initially hide the fieldset
		$(".qpp_option_list_settings").hide();
		validateInputs();

		function validateInputs() {
			// Check if checkbox is checked
			var fixedAmountChecked = $("input[name='fixedamount']").is(':checked');

			// Check if there's a comma in the input value
			var inputVal = $("input[name='inputamount']").val();
			var containsComma = inputVal && inputVal.includes(',');


			if (fixedAmountChecked && containsComma) {
				$(".qpp_option_list_settings").show();
			} else {
				$(".qpp_option_list_settings").hide();
			}
		}

		// Listen for changes in input text
		$("input[name='inputamount']").on('input', function (E) {
			validateInputs();
		});

		// Listen for changes in checkbox state
		$("input[name='fixedamount']").on('change', function () {
			validateInputs();
		});
	});
})( jQuery );
