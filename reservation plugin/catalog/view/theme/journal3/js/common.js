if (Journal.ocv == 4) {
	$(document).ready(function () {
		$.fn.jbutton = function (state) {
			return this.each(function () {
				var element = this;

				if (state == 'loading') {
					this.html = $(element).html();
					this.state = $(element).prop('disabled');

					$(element).prop('disabled', true).addClass('disabled')
				}

				if (state == 'reset') {
					$(element).prop('disabled', false).removeClass('disabled')
				}
			});
		}
		$.fn.button = $.fn.jbutton;
	});
} else {
	$.fn.jbutton = $.fn.button;
}

window['cart'] = window['cart'] || {};

window['cart'].add = function (product_id, quantity, quick_buy) {
	quantity = quantity || 1;

	$.ajax({
		url: Journal['add_cart_url'],
		type: 'post',
		data: 'product_id=' + product_id + '&quantity=' + quantity,
		dataType: 'json',
		beforeSend: function () {
			$('[data-toggle="tooltip"]').tooltip('hide');
			$('[onclick*="cart.add(\'' + product_id + '\'"]').jbutton('loading');
		},
		complete: function () {
			$('[onclick*="cart.add(\'' + product_id + '\'"]').jbutton('reset');
		},
		success: function (json) {
			$('.alert, .text-danger').remove();

			if (json['redirect']) {
				if (json['options_popup']) {
					var html = '';

					html += '<div class="popup-wrapper popup-options popup-iframe">';
					html += '	<div class="journal-loading"><em class="fa fa-spinner fa-spin"></em></div>';
					html += '	<div class="popup-container">';
					html += '		<div class="popup-body">';
					html += '		<div class="popup-inner-body">';
					html += '			<button class="btn popup-close"></button>';
					html += '			<iframe src="index.php?route=journal3/product&product_id=' + product_id + '&popup=options&product_quantity=' + quantity + (quick_buy ? '&quick_buy=true' : '') + (Journal['ocv'] == 4 ? '&language=' + Journal['language'] : '') + '" width="100%" height="100%" frameborder="0" onload="update_popup_height(this)"></iframe>';
					html += '		</div>';
					html += '		</div>';
					html += '	</div>';
					html += '	<div class="popup-bg popup-bg-closable"></div>';
					html += '</div>';

					// show modal
					$('.popup-wrapper').remove();

					$('body').append(html);

					setTimeout(function () {
						$('html').addClass('popup-open popup-center');
					}, 10);
				} else {
					location = json['redirect'];
				}
			}

			if (json['success']) {
				if (json['options_popup']) {
					var html = '';

					html += '<div class="popup-wrapper popup-options">';
					html += '	<div class="journal-loading"><em class="fa fa-spinner fa-spin"></em></div>';
					html += '	<div class="popup-container">';
					html += '		<div class="popup-body">';
					html += '		<div class="popup-inner-body">';
					html += '			<button class="btn popup-close"></button>';
					html += '			<iframe src="index.php?route=journal3/product&product_id=' + product_id + '&popup=options' + (quick_buy ? '&quick_buy=true' : '') + (Journal['ocv'] == 4 ? '&language=' + Journal['language'] : '') + '" width="100%" height="100%" frameborder="0" onload="update_popup_height(this)"></iframe>';
					html += '		</div>';
					html += '		</div>';
					html += '	</div>';
					html += '	<div class="popup-bg popup-bg-closable"></div>';
					html += '</div>';

					// show modal
					$('.popup-wrapper').remove();

					$('body').append(html);

					setTimeout(function () {
						$('html').addClass('popup-open popup-center');
					}, 10);
				} else {
					if (json['notification']) {
						show_notification(json['notification']);

						if (quick_buy) {
							location = Journal['checkoutUrl'];
						}
					} else {
						$('header').after('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
					}
				}

				// Need to set timeout otherwise it wont update the total
				setTimeout(function () {
					$('#cart-total').html(json['total']);
					$('#cart-items,.cart-badge').html(json['items_count']);

					if (json['items_count']) {
						$('#cart-items,.cart-badge').removeClass('count-zero');
						$('#cart').addClass('cart-has-items');
					} else {
						$('#cart-items,.cart-badge').addClass('count-zero');
						$('#cart').removeClass('cart-has-items');
					}
				}, 100);

				if (Journal['scrollToTop']) {
					$('html, body').animate({ scrollTop: 0 }, 'slow');
				}

				$('.cart-content ul').load(Journal['info_cart_url']);
/*cart counter code start*/				
setTimeout(function(){
	 $.get('index.php?route=checkout/cart/currentTime', function(response) {
		var serverTime = response.server_time; 
		startCountdownTimers(serverTime); 
	});
}, 1000);
/*cart counter code start*/   
				if (parent.window['_QuickCheckout']) {
					parent.window['_QuickCheckout'].save();
				} else if ($('html').hasClass('route-checkout-cart') || $('html').hasClass('route-checkout-checkout')) {
					parent.location.reload();
				}
			}
		},
		error: function (xhr, ajaxOptions, thrownError) {
			alert(thrownError + '\r\n' + xhr.statusText + '\r\n' + xhr.responseText);
		}
	});
};

window['cart'].remove = function (key) {
	$.ajax({
		url: Journal['remove_cart_url'],
		type: 'post',
		data: 'key=' + key,
		dataType: 'json',
		beforeSend: function () {
			$('#cart > button').jbutton('loading');
		},
		complete: function () {
			$('#cart > button').jbutton('reset');
		},
		success: function (json) {
			// Need to set timeout otherwise it wont update the total
			setTimeout(function () {
				$('#cart-total').html(json['total']);
				$('#cart-items,.cart-badge').html(json['items_count']);

				if (json['items_count']) {
					$('#cart-items,.cart-badge').removeClass('count-zero');
					$('#cart').addClass('cart-has-items');
				} else {
					$('#cart-items,.cart-badge').addClass('count-zero');
					$('#cart').removeClass('cart-has-items');
				}
			}, 100);

			if ($('html').hasClass('route-checkout-cart') || $('html').hasClass('route-checkout-checkout')) {
				location.reload();
			} else {
				$('.cart-content ul').load(Journal['info_cart_url']);
/*cart counter code start*/				
setTimeout(function(){
	 $.get('index.php?route=checkout/cart/currentTime', function(response) {
		var serverTime = response.server_time; 
		startCountdownTimers(serverTime); 
	});
}, 1000);
/*cart counter code start*/			   
			}
		},
		error: function (xhr, ajaxOptions, thrownError) {
			alert(thrownError + '\r\n' + xhr.statusText + '\r\n' + xhr.responseText);
		}
	});
};

window['cart'].update = function (key, quantity) {
	$.ajax({
		url: Journal['edit_cart_url'],
		type: 'post',
		data: 'key=' + key + '&quantity=' + (typeof (quantity) != 'undefined' ? quantity : 1),
		dataType: 'json',
		beforeSend: function () {
			$('#cart > button').jbutton('loading');
		},
		complete: function () {
			$('#cart > button').jbutton('reset');
		},
		success: function (json) {
			// Need to set timeout otherwise it wont update the total
			setTimeout(function () {
				$('#cart-total').html(json['total']);
				$('#cart-items,.cart-badge').html(json['items_count']);

				if (json['items_count']) {
					$('#cart-items,.cart-badge').removeClass('count-zero');
					$('#cart').addClass('cart-has-items');
				} else {
					$('#cart-items,.cart-badge').addClass('count-zero');
					$('#cart').removeClass('cart-has-items');
				}
			}, 100);

			if ($('html').hasClass('route-checkout-cart') || $('html').hasClass('route-checkout-checkout')) {
				location = 'index.php?route=checkout/cart';
			} else {
				$('.cart-content ul').load(Journal['info_cart_url']);
/*cart counter code start*/				
setTimeout(function(){
	 $.get('index.php?route=checkout/cart/currentTime', function(response) {
		var serverTime = response.server_time; 
		startCountdownTimers(serverTime); 
	});
}, 1000);
/*cart counter code start*/						   
			}
		},
		error: function (xhr, ajaxOptions, thrownError) {
			alert(thrownError + '\r\n' + xhr.statusText + '\r\n' + xhr.responseText);
		}
	});
};

window['wishlist'] = window['wishlist'] || {};

window['wishlist'].add = function (product_id) {
	$.ajax({
		url: Journal['add_wishlist_url'],
		type: 'post',
		data: 'product_id=' + product_id,
		dataType: 'json',
		success: function (json) {
			$('.alert').remove();

			if (json['redirect']) {
				location = json['redirect'];
			}

			if (json['success']) {
				$('[data-toggle="tooltip"]').tooltip('hide');

				if (json['notification']) {
					show_notification(json['notification']);
				} else {
					$('header').after('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
				}
			}

			$('#wishlist-total span').html(json['total']);
			$('#wishlist-total').attr('title', json['total']);
			$('.wishlist-badge').text(json['count']);

			if (json['count']) {
				$('.wishlist-badge').removeClass('count-zero');
			} else {
				$('.wishlist-badge').addClass('count-zero');
			}

			if (Journal['scrollToTop']) {
				$('html, body').animate({ scrollTop: 0 }, 'slow');
			}
		},
		error: function (xhr, ajaxOptions, thrownError) {
			alert(thrownError + '\r\n' + xhr.statusText + '\r\n' + xhr.responseText);
		}
	});
};

window['compare'] = window['compare'] || {};

window['compare'].add = function (product_id) {
	$.ajax({
		url: Journal['add_compare_url'],
		type: 'post',
		data: 'product_id=' + product_id,
		dataType: 'json',
		success: function (json) {
			$('.alert').remove();

			if (json['success']) {
				$('[data-toggle="tooltip"]').tooltip('hide');

				if (json['notification']) {
					show_notification(json['notification']);
				} else {
					$('header').after('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
				}

				$('#compare-total').html(json['total']);
				$('.compare-badge').text(json['count']);

				if (json['count']) {
					$('.compare-badge').removeClass('count-zero');
				} else {
					$('.compare-badge').addClass('count-zero');
				}

				if (Journal['scrollToTop']) {
					$('html, body').animate({ scrollTop: 0 }, 'slow');
				}
			}
		},
		error: function (xhr, ajaxOptions, thrownError) {
			alert(thrownError + '\r\n' + xhr.statusText + '\r\n' + xhr.responseText);
		}
	});
};

window['voucher'] = window['voucher'] || {};

window['voucher'].remove = function (key) {
	$.ajax({
		url: Journal['remove_cart_url'],
		type: 'post',
		data: 'key=' + key,
		dataType: 'json',
		beforeSend: function () {
			$('#cart > button').jbutton('loading');
		},
		complete: function () {
			$('#cart > button').jbutton('reset');
		},
		success: function (json) {
			// Need to set timeout otherwise it wont update the total
			setTimeout(function () {
				$('#cart-total').html(json['total']);
				$('#cart-items,.cart-badge').html(json['items_count']);

				if (json['items_count']) {
					$('#cart-items,.cart-badge').removeClass('count-zero');
				} else {
					$('#cart-items,.cart-badge').addClass('count-zero');
				}
			}, 100);

			if ($('html').hasClass('route-checkout-cart') || $('html').hasClass('route-checkout-checkout')) {
				location = 'index.php?route=checkout/cart';
			} else {
				$('.cart-content ul').load(Journal['info_cart_url']);
/*cart counter code start*/				
setTimeout(function(){
	 $.get('index.php?route=checkout/cart/currentTime', function(response) {
		var serverTime = response.server_time; 
		startCountdownTimers(serverTime); 
	});
}, 1000);
/*cart counter code start*/					   
			}
		},
		error: function (xhr, ajaxOptions, thrownError) {
			alert(thrownError + '\r\n' + xhr.statusText + '\r\n' + xhr.responseText);
		}
	});
};

window['update_popup_height'] = function (iframe) {
	const iframeDocument = iframe.contentWindow.document;
	let height = iframeDocument.body.clientHeight + 4;

	const buttons = iframeDocument.querySelector('.button-group-page');

	if (iframe.contentWindow.Journal['isQuickviewPopup']) {
		height += 20;
	}
	if (iframe.contentWindow.Journal['isOptionsPopup']) {
		height += buttons.offsetHeight;
	}

	iframe.style.height = height + 'px';

	document.documentElement.classList.add('popup-iframe-loaded');
};

window['quickview'] = function (product_id) {
	product_id = parseInt(product_id, 10);

	// hide tooltip
	$('[data-toggle="tooltip"]').tooltip('hide');

	var html = '';

	html += '<div class="popup-wrapper popup-quickview popup-iframe">';
	html += '	<div class="journal-loading"><em class="fa fa-spinner fa-spin"></em></div>';
	html += '	<div class="popup-container">';
	html += '		<div class="popup-body">';
	html += '			<div class="popup-inner-body">';
	html += '				<button class="btn popup-close"></button>';
	html += '				<iframe src="index.php?route=journal3/product&product_id=' + product_id + '&popup=quickview' + (Journal['ocv'] == 4 ? '&language=' + Journal['language'] : '') + '" width="100%" height="100%" frameborder="0" onload="update_popup_height(this)"></iframe>';
	html += '			</div>';
	html += '		</div>';
	html += '	</div>';
	html += '	<div class="popup-bg popup-bg-closable"></div>';
	html += '</div>';

	// show modal
	$('.popup-wrapper').remove();

	$('body').append(html);

	setTimeout(function () {
		$('html').addClass('popup-open popup-center');
	}, 10);
};

window['open_popup'] = function (module_id) {
	module_id = parseInt(module_id, 10);

	var html = '';

	html += '<div class="popup-wrapper popup-module popup-iframe">';
	html += '	<div class="journal-loading"><em class="fa fa-spinner fa-spin"></em></div>';
	html += '	<div class="popup-container">';
	html += '		<div class="popup-body">';
	html += '		<div class="popup-inner-body">';
	html += '			<button class="btn popup-close"></button>';
	html += '		</div>';
	html += '		</div>';
	html += '	</div>';
	html += '	<div class="popup-bg popup-bg-closable"></div>';
	html += '</div>';

	// show modal
	$('.popup-wrapper').remove();

	$('body').append(html);

	setTimeout(function () {
		$('html').addClass('popup-open popup-center');
	}, 10);

	$('.popup-container').css('visibility', 'hidden');

	$.ajax({
		url: 'index.php?route=journal3/popup' + Journal['route_separator'] + 'get&module_id=' + module_id + '&popup=module' + (Journal['ocv'] == 4 ? '&language=' + Journal['language'] : ''),
		success: function (html) {
			var $html = $(html);
			var $popup = $html.siblings('.module-popup');
			var $style = $html.siblings('style');
			var $content = $popup.find('.popup-container');

			$('#popup-style-' + module_id).remove();
			$('head').append($style.attr('id', 'popup-style-' + module_id));
			$('.popup-wrapper').attr('class', $popup.attr('class'));
			$('.popup-container').html($content.html());

			$('.popup-container').css('visibility', 'visible');

			Journal.lazy();
		},
		error: function (xhr, ajaxOptions, thrownError) {
			alert(thrownError + '\r\n' + xhr.statusText + '\r\n' + xhr.responseText);
		}
	});
};

window['open_login_popup'] = function () {
	var html = '';

	html += '<div class="popup-wrapper popup-login popup-iframe">';
	html += '	<div class="journal-loading"><em class="fa fa-spinner fa-spin"></em></div>';
	html += '	<div class="popup-container">';
	html += '		<div class="popup-body">';
	html += '		<div class="popup-inner-body">';
	html += '			<button class="btn popup-close"></button>';
	html += '			<iframe src="index.php?route=account/login&popup=login' + (Journal['ocv'] == 4 ? '&language=' + Journal['language'] : '') + '" width="100%" height="100%" frameborder="0" onload="update_popup_height(this)"></iframe>';
	html += '		</div>';
	html += '		</div>';
	html += '	</div>';
	html += '	<div class="popup-bg popup-bg-closable"></div>';
	html += '</div>';

	// show modal
	$('.popup-wrapper').remove();

	$('body').append(html);

	setTimeout(function () {
		$('html').addClass('popup-open popup-center');
	}, 10);
};

window['open_register_popup'] = function () {
	var html = '';

	html += '<div class="popup-wrapper popup-register popup-iframe">';
	html += '	<div class="journal-loading"><em class="fa fa-spinner fa-spin"></em></div>';
	html += '	<div class="popup-container">';
	html += '		<div class="popup-body">';
	html += '		<div class="popup-inner-body">';
	html += '			<button class="btn popup-close"></button>';
	html += '			<iframe src="index.php?route=account/register&popup=register' + (Journal['ocv'] == 4 ? '&language=' + Journal['language'] : '') + '" width="100%" height="100%" frameborder="0" onload="update_popup_height(this)"></iframe>';
	html += '		</div>';
	html += '		</div>';
	html += '	</div>';
	html += '	<div class="popup-bg popup-bg-closable"></div>';
	html += '</div>';

	// show modal
	$('.popup-wrapper').remove();

	$('body').append(html);

	setTimeout(function () {
		$('html').addClass('popup-open popup-center');
	}, 10);
};

window['show_notification'] = function (opts) {
	opts = $.extend({
		position: 'center',
		className: '',
		title: '',
		image: '',
		message: '',
		buttons: [],
		timeout: Journal.notificationHideAfter
	}, opts);

	if ($('.notification-wrapper-' + opts.position).length === 0) {
		$('body').append('<div class="notification-wrapper notification-wrapper-' + opts.position + '"></div>');
	}

	var html = '';

	var buttons = $.map(opts.buttons, function (button) {
		return '<a class="' + button.className + '" href="' + button.href + '">' + button.name + '</a>';
	});

	html += '<div class="notification ' + opts.className + '">';
	html += '	<button class="btn notification-close"></button>';
	html += '	<div class="notification-content">';

	if (opts.image) {
		html += '		<img src="' + opts.image + '" srcset="' + opts.image + ' 1x, ' + opts.image2x + ' 2x">';
	}

	html += '		<div>';
	html += '			<div class="notification-title">' + opts.title + '</div>';
	html += '			<div class="notification-text">' + opts.message + '</div>';
	html += '		</div>';
	html += '	</div>';

	if (buttons && buttons.length) {
		html += '<div class="notification-buttons">' + buttons.join('\n') + '</div>';
	}

	html += '</div>';

	var $notification = $(html);

	$('.notification-wrapper-' + opts.position).append($notification);

	if (opts.timeout) {
		setTimeout(function () {
			$notification.find('.notification-close').trigger('click');
		}, opts.timeout);
	}

	return $notification;
};

window['show_message'] = function (opts) {
	opts = $.extend({
		position: 'message',
		className: '',
		message: '',
		timeout: 0
	}, opts);

	parent.$('.notification-wrapper-' + opts.position).remove();

	parent.show_notification(opts);
}

window['loader'] = function (el, status) {
	var $el = $(el);

	if (status) {
		$el.attr('style', 'position: relative');
		$el.append('<div class="journal-loading-overlay"><div class="journal-loading"><em class="fa fa-spinner fa-spin"></em></div></div>');
	} else {
		$el.attr('style', '');
		$el.find('.journal-loading-overlay').remove();
	}
};

/*cart counter code start*/
function parseDateTime(dateTimeStr) {
    var dateTimeParts = dateTimeStr.split(' ');
    var dateParts = dateTimeParts[0].split('-');
    var timeParts = dateTimeParts[1].split(':');
    
    // Create a new Date object using UTC to avoid timezone issues
    return new Date(Date.UTC(
        dateParts[0],    // Year
        dateParts[1] - 1, // Month (0-based in JavaScript)
        dateParts[2],    // Day
        timeParts[0],    // Hours
        timeParts[1],    // Minutes
        timeParts[2]     // Seconds
    )).getTime() / 1000; // Convert to seconds since epoch
}

// Function to start the countdown for each cart item
function startCountdownTimers(serverTime) {
    var countdownElements = document.querySelectorAll('.countdown-timer');
    var serverTimestamp = parseDateTime(serverTime); // Convert server time to timestamp

    countdownElements.forEach(function (element) {
        var timeAdded = element.getAttribute('data-time-added'); // Get the time item was added
        
        var timeAddedTimestamp = parseDateTime(timeAdded); // Parse 'YYYY-MM-DD HH:MM:SS' into timestamp (in seconds)
        var elapsedTime = serverTimestamp - timeAddedTimestamp; // Time passed since item was added (based on server time)
        
        var remainingTime = 1200 - elapsedTime; // Remaining time: 1 hour (3600 seconds) minus elapsed time

        if (remainingTime > 0) {
            var timer = setInterval(function () {
                remainingTime--;

                // Calculate minutes and seconds remaining
                var minutes = Math.floor(remainingTime / 60);
                var seconds = remainingTime % 60;
                seconds = seconds < 10 ? '0' + seconds : seconds;
                if (remainingTime <= 0) {
                    clearInterval(timer);
                    element.innerHTML = "<span>Artikel je potekel!</span>";
                } else {
                    element.innerHTML = `Vaš predmet je shranjen <span>${minutes}:${seconds}</span> minut!`;
                }
            }, 1000); // Update every second
        } else {
            element.innerHTML = "<span>Artikel je potekel!</span>";
        }
    });
}

// Fetch the server time using AJAX and start the countdown timers
$(document).ready(function() {
    $.get('index.php?route=checkout/cart/currentTime', function(response) {
        var serverTime = response.server_time; // Get server time in 'YYYY-MM-DD HH:MM:SS' format
        startCountdownTimers(serverTime); // Start the countdown using server time
    });
});
/*cart counter code end*/