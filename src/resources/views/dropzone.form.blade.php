<form action="{{ route('attachments.dropzone')  }}" class="dropzone" id="my-dropzone">
{{ csrf_field() }}
</form>

<script>
    Dropzone.options.myDropzone = {
        init: function () {
            this.on("success", function (file, response) {
                let container = document.getElementById("{{$drop_zone_form}}");
                let input = document.createElement("input");
                input.type = "hidden";
                input.name = "attachments[]";
                input.value = response.uuid;
                container.appendChild(input);
            });
        }
    };
</script>