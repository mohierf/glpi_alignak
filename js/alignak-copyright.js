var alignak_footer = '' +
    '<br/>' +
    'Copyleft <span style="display:inline-block;transform: rotate(180deg);font-size: 12px;">&copy;</span> 2018 - ' +
    '<a class="copyright" href="http://alignak.net"> Alignak Team</a>';

$(window).bind("load", function() {
   $('#footer').css('height', 'auto');
   $("#footer td.right").append(alignak_footer);
});
