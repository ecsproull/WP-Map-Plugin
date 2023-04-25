jQuery( document ).ready( function($){
    $( "#phone_input" ).keyup( function() {
        const formattedInputValue = formatPhoneNumber($( "#phone_input" ).val());
        $( "#phone_input" ).val(formattedInputValue);
    });

    $('#trip').change(function(){
      if (typeof place_select_form != undefined) {
        place_select_form.submit();
      }
  });

    function formatPhoneNumber(value) {
        if (!value) {
             return value;
        }

        const phoneNumber = value.replace(/[^\d]/g, '');
      
        // phoneNumberLength is used to know when to apply our formatting for the phone number
        const phoneNumberLength = phoneNumber.length;
      
        // we need to return the value with no formatting if its less than four digits
        // this is to avoid weird behavior that occurs if you  format the area code too early
        if (phoneNumberLength < 4) return '(' + phoneNumber;
      
        // if phoneNumberLength is greater than 4 and less the 7 we start to return
        // the formatted number
        if (phoneNumberLength < 7) {
          return `(${phoneNumber.slice(0, 3)})${phoneNumber.slice(3)}`;
        }
      
        // finally, if the phoneNumberLength is greater then seven, we add the last
        // bit of formatting and return it.
        return `(${phoneNumber.slice(0, 3)})${phoneNumber.slice(
          3,
          6
        )}-${phoneNumber.slice(6, 10)}`;
      }
});