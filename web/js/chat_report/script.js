$(document).on('click', '.ajax-link', function (e){
    e.preventDefault();

    let url = $(this).attr('href');

    return getContent(url);
});

function getContent(url, params = {}){

    $.ajax({
        url: url,
        type: 'POST',
        data: params,
        beforeSend: function (){
            $('.content').html('<div class="wrapper-loading">\n' +
                '    <span class="spinner"></span>\n' +
                '</div>');
        },
        success: function(content){
            $('.content').html($(content).find('.content').html());
        },
        error: function(){
            alert('Error!');
        }
    });

    return true;
}

function selectEmployees() {
    BX24.selectUsers(function(result) {
        if (result) {

            $(".btn-delete-user").click();
            
            result.forEach(function (user){
                $(".selected-user").prepend("<span data='U" + user.id + "'>" + user.name + "<button type='button' class='btn-delete-user'><i class='fal fa-times'></i></button></span>");
            });

            var selectedUsers = result.map(function(user) {
                return user.id;
            });
            // Заполнение скрытого поля "employee" перед отправкой формы
            document.getElementById('employee').value = JSON.stringify(selectedUsers);
            // Отправка формы
            document.getElementById('mainForm').submit();
        }
    });

    $(document).on("click", ".btn-delete-user", function(){
        $(this).parent().remove();
    });
}

$(document).on('submit', '#filter-form', function (e){
    e.preventDefault();

    let data = $(this).serialize();
    let url = $(this).attr('action');

    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        beforeSend: function (){
            $('.wrapper-block').remove();
            $('.content').prepend('<div class="wrapper-loading">\n' +
                '    <span class="spinner"></span>\n' +
                '</div>');
        },
        success: function(content){
            $('.content').html($(content).find('.content').html());
        },
        error: function(content){
            alert('Error!');
        }
    });
})