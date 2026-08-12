document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const refCode = urlParams.get('ref');

    if (refCode) {
        const invitationIdField = document.querySelector('#um_field_invitation_id_287');
        if (invitationIdField) {
            invitationIdField.value = refCode;
            invitationIdField.readOnly = true;
        }
    }
});
