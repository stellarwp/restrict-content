/**
 * Admin Reports
 *
 * @since 3.3
 */
jQuery( document ).ready( function ( $ ) {

	var RCP_Reports = {

		membershipCountsGraph: false,

		filterButton: false,

		/**
		 * Initialize
		 */
		init: function () {

			this.filterButton = $( '#rcp-graphs-filter .button-secondary' );
			this.getMembershipCountsData();

			// Update the graph when the filters change.
			$( '#rcp-graphs-filter' ).on( 'submit', function( e ) {
				e.preventDefault();

				RCP_Reports.getMembershipCountsData();
			} );

		},

		/**
		 * Parse the selected filters
		 */
		parseFilters: function ( filters ) {

			let dateOption = $( '#rcp-graphs-date-options' );
			let levelsOption = $( '#rcp-graphs-subscriptions' );
			let statusOption = $( '#rcp-graphs-membership-status' );

			if ( dateOption.length ) {
				filters.range = dateOption.val();

				if ( 'other' === filters.range ) {
					filters.m_start = $( '#rcp-graphs-month-start' ).val();
					filters.year = $( '#rcp-graphs-year-start' ).val();
					filters.m_end = $( '#rcp-graphs-month-end' ).val();
					filters.year_end = $( '#rcp-graphs-year-end' ).val();
				}
			}

			if ( levelsOption.length ) {
				filters.level_id = levelsOption.val();
			}

			if ( statusOption.length ) {
				filters.membership_status = statusOption.val();
			}

			return filters;

		},

		/**
		 * Get the membership counts graph data
		 */
		getMembershipCountsData: function () {

			RCP_Reports.filterButton.data( 'text', RCP_Reports.filterButton.attr( 'value' ) ).attr( 'value', 'Please wait...' );
			RCP_Reports.filterButton.prop( 'disabled', true );

			let args = {
				action: 'rcp_get_membership_counts_report_data',
				nonce: $( '#rcp-reports-wrap' ).data( 'nonce' ),
			};

			args = RCP_Reports.parseFilters( args );

			console.log('Ajax args', args);

			$.ajax( {
				type: "POST",
				data: args,
				dataType: "json",
				url: ajaxurl,
				success: function ( response ) {
					console.log('Report data', response);

					RCP_Reports.filterButton.attr( 'value', RCP_Reports.filterButton.data( 'text' ) ).prop( 'disabled', false );

					if ( response.success ) {

						if ( false !== RCP_Reports.membershipCountsGraph ) {
							RCP_Reports.membershipCountsGraph.destroy();
						}

						RCP_Reports.membershipCountsGraph = new Chart( document.getElementById( 'rcp-membership-counts-graph-canvas' ).getContext( '2d' ), response.data );

						return response.data;
					}

					return false;
				}
			} );

		},

	};

	RCP_Reports.init();

} );
