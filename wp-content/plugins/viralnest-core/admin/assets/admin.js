/* ViralNest admin: media pickers, gallery picker, sortable lists. */
(function ($) {
	'use strict';

	$(document).on('click', '[data-vn-media-pick]', function (e) {
		e.preventDefault();
		var wrap = $(this).closest('[data-vn-media]');
		var frame = wp.media({ title: $(this).data('title') || 'Choose image', library: { type: 'image' }, multiple: false });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			wrap.find('input[type=hidden]').val(a.id);
			var src = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
			wrap.find('.vn-media__preview').html($('<img alt="">').attr('src', src));
		});
		frame.open();
	});

	$(document).on('click', '[data-vn-media-clear]', function (e) {
		e.preventDefault();
		var wrap = $(this).closest('[data-vn-media]');
		wrap.find('input[type=hidden]').val('0');
		wrap.find('.vn-media__preview').empty();
	});

	$(document).on('click', '[data-vn-gallery-pick]', function (e) {
		e.preventDefault();
		var wrap = $(this).closest('[data-vn-gallery]');
		var input = wrap.find('input[type=hidden]');
		var frame = wp.media({ title: 'Project gallery', library: { type: 'image' }, multiple: 'add' });
		frame.on('open', function () {
			var sel = frame.state().get('selection');
			(input.val() || '').split(',').filter(Boolean).forEach(function (id) {
				var att = wp.media.attachment(id); att.fetch(); sel.add(att);
			});
		});
		frame.on('select', function () {
			var items = frame.state().get('selection').toJSON();
			input.val(items.map(function (a) { return a.id; }).join(','));
			var box = wrap.find('.vn-gallery').empty();
			items.forEach(function (a) {
				var src = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
				box.append($('<img alt="">').attr('src', src));
			});
		});
		frame.open();
	});

	$(function () {
		if ($.fn.sortable) {
			$('[data-vn-sortable]').sortable({ handle: '.dashicons-menu', axis: 'y' });
		}
		$('[data-vn-color]').on('input change', function () {
			if (/^#[0-9a-f]{6}$/i.test(this.value)) {
				$(this).siblings('.vn-color__swatch').css('background', this.value);
			}
		});
	});
})(jQuery);
