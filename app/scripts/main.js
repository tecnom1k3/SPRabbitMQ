$(function(){
    $('#btbLogin').click(function(e) {
        e.preventDefault();
        $.post('login.php', $('#frmLogin').serialize(), function(data){
            var result = $.parseJSON(data);
            alert(result.status);
        });
    });
});