jQuery(document).ready(function ($) {
	var searchField = $('input[name="thinh_debug_hook_name"]');
	var suggestionBox = $('<div class="hook-suggestions"></div>').insertAfter(searchField);

	searchField.on('keyup', function () {
		var searchTerm = $(this).val();
		$.post(thinhDebugAjax.ajax_url, {
			action: 'thinh_debug_search_hooks',
			search: searchTerm
		}, function (response) {
			if (response.success) {
				suggestionBox.empty();
				response.data.forEach(function (hook) {
					var suggestionItem = $('<div class="suggestion-item"></div>').text(hook);
					suggestionItem.on('click', function () {
						searchField.val($(this).text());
						suggestionBox.empty();
					});
					suggestionBox.append(suggestionItem);
				});
			}
		});
	});

	// Hide suggestions when clicking outside
	$(document).on('click', function (event) {
		if (!$(event.target).closest('.hook-suggestions, input[name="thinh_debug_hook_name"]').length) {
			suggestionBox.empty();
		}
	});
});
