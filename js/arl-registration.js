jQuery(document).ready(function (){
   
	// Perform AJAX login on form submit
	jQuery('form#form-login').on('submit', function(e){
		loader = '<div class="icon_loader la-ball-scale-multiple"><div></div><div></div><div></div></div>';
		
		jQuery('form#form-login .status_msg').show().html(loader);
		
		jQuery.ajax({
			type: 'POST',
			dataType: 'json',
			url: ajax_login_object.ajaxurl,
			data: {
			   'action': 'ajaxlogin', //calls wp_ajax_nopriv_ajaxlogin
			   'username': jQuery('form#form-login #user_login').val(),
			   'password': jQuery('form#form-login #user_pass').val(),
			   'security': jQuery('form#form-login #security').val()
			},
			success: function(data){
				
				if (data.loggedin === true) {
					setTimeout(function() { location.reload(); }, 100);
				} else {
					jQuery('form#form-login .status_msg').html(data.message);
				}
			}
		});
		e.preventDefault();
	});
   
	// Send a new password if lost
	jQuery('form#lost-pass').on('submit', function(e){
	
		var form = jQuery(this);
	
		form.find('.error,.success').remove();
	
		// Send AJAX call
		jQuery.ajax({
			type: 'POST',
			dataType: 'json',
			url: ajax_login_object.ajaxurl,
			data: {
				'action': 'lost_password', //calls wp_ajax_nopriv_lost_password
				'login': form.find('#user_login_pass').val()
			},
			success: function(r){
			if (r.success){
				// If success
				jQuery('<div/>',{
					html: '<div class="alert alert-success" role="alert">' + r.data + '</div>',
					'class': 'm-t'
				}).appendTo(form);
			} else {
				// else...
				jQuery('<div/>',{
					html: '<div class="alert alert-danger" role="alert">' + r.data + '</div>',
					'class': 'm-t'
					}).appendTo(form);
				}
			}
		});
		e.preventDefault();
	});

	// AJAX Registration to wp
	jQuery('form#registration').on('submit', function(e){
	
		var form = jQuery(this);
		
		// Loading
		jQuery('<div/>', {
			html: '<div class="la-ball-scale-multiple"><div></div><div></div><div></div></div>',
			'class' : 'icon_loader'
		}).appendTo(form);
	
		// Send AJAX call
		jQuery.ajax({
			type: 'POST',
			dataType: 'json',
			url: ajax_login_object.ajaxurl,
			data: {
				'action': 'register_user', //calls wp_ajax_nopriv_register_user
				'username': form.find('#username').val(),
				'email': form.find('#email').val(),
				'pwd1': form.find('#pwd1').val(),
				'pwd2': form.find('#pwd2').val(),
				'privacy': (form.find('#user_privacy').is(':checked')) ? 1 : 0
			},
			success: function(r){
	
				jQuery('.icon_loader').remove();
				jQuery('.display_msg').remove();
	
				if (r.success){
					// If success
					jQuery('<div/>',{
						html: r.data.message,
						'class': 'm-t display_msg'
					}).appendTo(form);
					// Reload the page cause the user should be logged in by now
					setTimeout(function() { location.reload(); }, 4000);
				} else {
					// else...
					jQuery('<div/>',{
						html: r.data.message,
						'class': 'm-t display_msg'
					}).appendTo(form);
				}
			}
		});
		e.preventDefault();
	});
	  

});