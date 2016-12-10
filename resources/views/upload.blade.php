<form method="POST" action="/api/file/upload" enctype="multipart/form-data">
    <input type="file" name="files">

    <input type="hidden" name="_token"         value="{{csrf_token()}}"/>
    <input type="submit" name="上传">
</form>