/*!
 * @package   yii2-assets
 * @author    Kartik Visweswaran <kartikv2@gmail.com>
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2021
 * @version   1.4.2
 *
 * Columns Selector Validation Module.
 *
 */
(function ($) {
	"use strict";

	var SelectRows = function (element, options) {
		var self = this;
		self.$element = $(element);
		self.options = options;
		self.listen();
	};

	SelectRows.prototype = {
		constructor: SelectRows,
		listen: function () {
			var self = this, $el = self.$element, $tog = $el.find('input[type="checkbox"].select-on-check-all');
			$el.off('click').on('click', function (e) {
				e.stopPropagation();
			});
			$tog.off('change').on('change', function () {
				var checked = $tog.is(':checked');
				$el.find('input[type=checkbox].select-on-check-row:not([disabled])').prop('checked', checked);
			});
		}
	};

	//ExportColumns plugin definition
	$.fn.selectRows = function (option) {
		var args = Array.apply(null, arguments);
		args.shift();
		return this.each(function () {
			var $this = $(this),
				data = $this.data('selectRows'),
				options = typeof option === 'object' && option;

			if (!data) {
				$this.data('selectRows', (data = new SelectorColumns(this,
					$.extend({}, $.fn.selectRows.defaults, options, $(this).data()))));
			}

			if (typeof option === 'string') {
				data[option].apply(data, args);
			}
		});
	};

	$.fn.selectRows.defaults = {};

})(window.jQuery);
