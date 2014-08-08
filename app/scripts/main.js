$(function(){
    $('#btbLogin').click(function(e) {
        e.preventDefault();
        $.post('login.php', $('#frmLogin').serialize(), function(data){
            var result = $.parseJSON(data);
            alert(result.status);
        });
    });
    
    $("#btnInvoiceGenerate").click(function(e) {
        e.preventDefault();
        $.post('generateInvoice.php', $("#frmInvoice").serialize(), function(data) {
            alert('Invoice Generated');
        })
    });
});