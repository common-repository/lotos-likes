jQuery(function($) {

  $('.lotos-likes').on('click', function() {
    var link = $(this);
    if (link.hasClass('active')) return false;

    var id = $(this).attr('id'),
        postfix = link.find('.lotos-likes-postfix').text();

    $.ajax({
      type: 'POST',
      url: lotos_likes.ajaxurl,
      data: {
        action: 'lotos-likes', 
        likes_id: id, 
        postfix: postfix, 
      },
      xhrFields: { 
        withCredentials: true, 
      },
      success: function(data) {
        link.html(data).addClass('active').attr('title','You already like this');
      },
    });

    return false;
  });

  if ($('body.ajax-lotos-likes').length) {
    $('.lotos-likes').each(function() {
      var id = $(this).attr('id');
      $(this).load(lotos_likes.ajaxurl, {
        action: 'lotos-likes', 
        post_id: id,
      });
    });
  }
});
