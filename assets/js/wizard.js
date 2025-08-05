jQuery(function ($) {
  function showStep(step) {
    $('.wizard-panel').hide();
    $('.wizard-panel.step-' + step).show();
    $('.step').removeClass('active');
    $('.step[data-step="' + step + '"]').addClass('active');
  }
  $('.wizard-next').on('click', function () {
    const next = $(this).data('next');
    showStep(next);
  });
  $('.wizard-prev').on('click', function () {
    const prev = $(this).data('prev');
    showStep(prev);
  });
  $('.wizard-finish').on('click', function () {
    const shippingInstance = $('#tisak-shipping-instance').val();
    $.ajax在天

System: ajax({
      url: tisakWizard.ajax_url,
      type: 'POST',
      data: {
        action: 'tisak_wizard_save',
        shipping_method_instance: shippingInstance,
        nonce: tisakWizard.nonce
      },
      success: function (response) {
        if (response.success) {
          window.location.href = response.data.redirect;
        } else {
          alert('Greška: postavke nisu spremljene.');
        }
      },
      error: function () {
        alert('Došlo je do greške pri komunikaciji s poslužiteljem.');
      }
    });
  });
});