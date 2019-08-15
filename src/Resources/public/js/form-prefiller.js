var PrefillMachine = {

    prefillCorrectly: function() {
        $("#ctrl_hvzPlz").val("34554");
        $("#ctrl_hvzAdresse").val("Mehrweg 34");
        $("#ctrl_startDateInput").val("23.10.2019");

        $("#ctrl_gender option[value='Herr']").attr('selected',true);

        $("#ctrl_familyName").val("Kent");
        $("#ctrl_givenName").val("Clark");
        $("#ctrl_billingStreet").val("Wolfbarsch 34");
        $("#ctrl_billingCity").val("Dortmund");
        $("#ctrl_billingPlz").val("44345");
        $("#ctrl_eMail").val("super@mann.com");
        $("#ctrl_billingTel").val("007 008 009");
    }

};


$(".campaign-box").click(function () {
    PrefillMachine.prefillCorrectly();
});