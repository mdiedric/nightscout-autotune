$(function(){

  $('#form_submit_button').click(function(e){
    e.preventDefault();

    // nsurlvalidate_url is defined in global scope in the nsprofile template
    // probably a better way to do this
    var posting = $.post(
      json_profile_url,
      JSON.stringify({ nsurl : $('#ns_url').val() })
    );
    $('#outputs').show();
    $('#outputs > pre').text('Loading...');

    posting.done(function( data ) {
      console.log(data);
      $('#download-button').show();
      $('#outputs > pre').text(JSON.stringify(data, null, 4));
    });

    posting.fail(function( data ) {
      $('#outputs > pre').text('Please check your Nightscout URL');
    });
  });

});
