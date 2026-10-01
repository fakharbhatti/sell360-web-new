// AJAX submit + validation for forms with class "ajax-form" (contact, get demo).
// Each form needs: an action URL, a submit button and a .form-msg element.
$(document).on('submit', '.ajax-form', function (e) {
	e.preventDefault();
	var $form = $(this), $btn = $form.find('[type=submit]'), $msg = $form.find('.form-msg');
	var btnText = $btn.data('text') || $btn.text();
	$btn.data('text', btnText);

	$msg.removeClass('success error').text('');
	$form.find('.is-invalid').removeClass('is-invalid');

	var $empty = $form.find('[required]').filter(function () { return !$.trim(this.value); });
	if ($empty.length) {
		$empty.addClass('is-invalid').first().focus();
		$msg.addClass('error').text('Please fill in all required fields.');
		return;
	}
	var $email = $form.find('input[type=email]');
	if ($email.length && $.trim($email.val()) && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($.trim($email.val()))) {
		$email.addClass('is-invalid').focus();
		$msg.addClass('error').text('Please enter a valid email address.');
		return;
	}
	var $phone = $form.find('input[type=tel]');
	if ($phone.length && $.trim($phone.val()) && !/^\+?[0-9\s\-()]{7,20}$/.test($.trim($phone.val()))) {
		$phone.addClass('is-invalid').focus();
		$msg.addClass('error').text('Please enter a valid phone number.');
		return;
	}

	$btn.prop('disabled', true).text('SENDING...');
	$.ajax({
		url: $form.attr('action'),
		type: 'POST',
		data: $form.serialize(),
		dataType: 'json'
	}).done(function (res) {
		if (res.success) {
			$msg.addClass('success').text(res.message);
			$form[0].reset();
		} else {
			$msg.addClass('error').text(res.message);
		}
	}).fail(function (xhr) {
		var res = xhr.responseJSON;
		$msg.addClass('error').text(res && res.message ? res.message : 'Something went wrong. Please try again.');
	}).always(function () {
		$btn.prop('disabled', false).text(btnText);
	});
});

$(document).on('input', '.ajax-form .is-invalid', function () {
	$(this).removeClass('is-invalid');
});
