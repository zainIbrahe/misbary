<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form action="{{route('import')}}" method="post" enctype="multipart/form-data">
		@csrf
        <input type="file" name="file" id="">

        <input type="submit" value="Import">
    </form>
</body>

</html>