var PrefillMachine = {

    prefillCorrectly: function() {
        $("#ctrl_hvzPlz").val("34554");
        $("#ctrl_hvzAdress").val("Mehrweg 34");
        $("#ctrl_startDateInput").val("23.10.2019");

        $("#gender option[value='Herr']").attr('selected',true);

        $("#familyName").val("Kent");
        $("#givenName").val("Clark");
        $("#billingStreet").val("Wolfbarsch 34");
        $("#billingCity").val("Dortmund");
        $("#billingPlz").val("44345");
        $("#eMail").val("super@mann.com");
    }

};

