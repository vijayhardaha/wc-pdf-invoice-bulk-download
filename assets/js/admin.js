( function( $ ) {
  const APP = {
    init: () => {
      APP.dismissIcon = `<span role="button" tabindex="0" class="dismiss"><svg height="24" width="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"></path></g></svg></span>`;
      APP.noticeIcon = `<span class="notice-icon"><svg height="24" width="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"></path></g></svg></span>`;
      APP.checkIcon = `<span class="notice-icon"><svg height="24" width="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path d="M9 19.414l-6.707-6.707 1.414-1.414L9 16.586 20.293 5.293l1.414 1.414"></path></g></svg></span>`;

      APP.noticeTimeout = false;

      // Register sales report events.
      APP.registerDownloadPDFEvents();
    },
    getElement: function( selector ) {
      return $( document ).find( selector );
    },
    showNotice: ( type = "info", text = "", autohide = "yes" ) => {
      let html;

      clearTimeout( APP.noticeTimeout );

      APP.getElement( ".notices-container" ).remove();

      if ( text == "" ) {
        switch ( type ) {
          case "success":
            text = "Updated settings.";
            break;
          case "error":
            text = "Update settings failed.";
            break;
          case "warning":
            text = "Update settings failed.";
            break;
          case "info":
          default:
            text = "Updating settings...";
            break;
        }
      }

      if ( type === "success" ) {
        html = `<div class="notices-container"><div class="notice-box is-${type}">${APP.checkIcon}<span class="notice-content"><span class="notice-text">${text}</span></span>${APP.dismissIcon}</div></div>`;
      } else {
        html = `<div class="notices-container"><div class="notice-box is-${type}">${APP.noticeIcon}<span class="notice-content"><span class="notice-text">${text}</span></span>${APP.dismissIcon}</div></div>`;
      }

      APP.getElement( ".wc-pdf-invoices-bulk-download-container" ).append( html );

      if ( autohide === "yes" ) {
        APP.noticeTimeout = setTimeout( function() {
          APP.getElement( ".notices-container" ).fadeOut().remove();
        }, 5000 );
      }
    },
    removeAllNotices: () => {
      APP.getElement( ".notices-container" ).remove();
    },
    registerNoticeEvents: () => {
      $( document )
        .on( "click", ".notice-box .dismiss", ( e ) => {
          e.preventDefault();
          $( e.currentTarget ).parents( ".notice-box" ).hide().remove();
          if ( APP.getElement( ".notices-container" ).length && APP.getElement( ".notices-container" ).html().trim() == "" ) {
            APP.getElement( ".notices-container" ).remove();
          }
        } );
    },
    registerDatePickersEvents: () => {
      if ( APP.getElement( ".setting-field .datepicker" ).length ) {
        APP.getElement( ".setting-field .datepicker" ).datepicker( {
          dateFormat: "yy-mm-dd",
          changeMonth: true,
          changeYear: true,
          yearRange: "-100:+1",
          maxDate: "0"
        } );
      }
    },
    registerDownloadPDFEvents: () => {
      // Register notice events.
      APP.registerNoticeEvents();

      //Register date picker events
      APP.registerDatePickersEvents();

      APP.switchReportPeriod();

      $( document )
        .on( "submit", "form.wc-pdf-invoices-bulk-download-form", function( e ) {
          e.preventDefault();

          var form = $( this );

          APP.removeAllNotices();

          $.ajax( {
            type: 'POST',
            url: ajaxurl,
            data: form.serialize(),
            dataType: 'json',
            beforeSend: function() {
              form.find( '.btn' ).attr( 'disabled', true );

              APP.showNotice( "info", wc_pdf_invoices_bulk_download_admin_params.messages.processing, "no" );

              $( 'html,body' ).animate( {
                scrollTop: 0
              }, 800 );
            },
            success: function( response ) {
              form.find( '.btn' ).attr( 'disabled', false );

              if ( response.success ) {
                if ( false !== response.data ) {
                  APP.showNotice( "success", wc_pdf_invoices_bulk_download_admin_params.messages.success );
                  window.location = response.data;
                  return 1;
                }
              } else {
                var error = wc_pdf_invoices_bulk_download_admin_params.messages.general_error;
                if ( response.data ) {
                  error = response.data;
                }
                APP.showNotice( "error", error );
              }
            },
            error: function( response, statusText, errorText ) {
              form.find( '.btn' ).attr( 'disabled', false );

              var error = wc_pdf_invoices_bulk_download_admin_params.messages.server_error;
              if ( errorText ) {
                error = errorText + '. ' + error;
              }
              APP.showNotice( "error", error );
            }
          } );
        } )

        .on( "change", ".wc-pdf-invoices-bulk-download-form :input#setting-download-filter", ( e ) => {
          APP.switchReportPeriod( $( e.currentTarget ).val() );
        } );
    },
    switchReportPeriod: ( value = '' ) => {
      if ( value == '' ) {
        value = APP.getElement( ".wc-pdf-invoices-bulk-download-form :input#setting-download-filter" ).val();
      }
      if ( value === "range-group" ) {
        APP.getElement( ".wc-pdf-invoices-bulk-download-form #setting-row-month-year-range" ).hide();
        APP.getElement( ".wc-pdf-invoices-bulk-download-form #setting-row-custom-date-range" ).slideDown();
      } else {
        APP.getElement( ".wc-pdf-invoices-bulk-download-form #setting-row-custom-date-range" ).hide();
        APP.getElement( ".wc-pdf-invoices-bulk-download-form #setting-row-month-year-range" ).slideDown();
      }
    },
  };

  APP.init();
} )( jQuery );