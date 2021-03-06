$(document).on("click", "#addBrand", function (){
    var value = $("#brand").clone().prepend(
        '<button id="removeBrand" type="button" class="btn btn-flat btn-danger btn-xs pull-right" data-toggle="tooltip" data-placement="top" title="Remove">' +
        '<i class="glyphicon glyphicon-remove"></i>' +
        '</button>').appendTo('#brands');
    $(value).find("input").val("");
});

$(document).on("click", "#removeBrand", function (){
    id = $(this).parent().find('input.hidden').val();
    context = $(this).parent();
    if(id!=null){
        $.ajax({
            type: "GET",
            url: "/type/remove/"+id,
            dataType: "JSON",
            success:function(data){
                if(data.message==0){
                    $(context).remove();
                }else{
                    $('#notif').append(
                        '<div class="alert alert-danger alert-dismissible">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
                        '<h4><i class="icon fa fa-ban"></i> Something went wrong!</h4>' +
                        data.message +
                        '</div>'
                    );
                }
            }
        });
    }else{
        $(context).remove();
    }
});

