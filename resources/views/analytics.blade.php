<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body class="w3-content">
        <a href="{{ route('google-analytics-summary') }}"> Summary </a>
        <table>
            <tr>
                <th> Redirected URL</th>
                <th> Advertise </th>
                <th> Date </th>
                <th> User IP </th>
                <th> Total Event</th>
            </tr>
            <?php $length = count($web_data) ?> 
            @for($i=0;$i<$length;$i++)
                <tr>
                    <td>
                        {{ $web_data[$i][0] }}
                    </td>
                    <td>
                        {{ $web_data[$i][3] }}
                    </td>
                    <td>
                        {{ $web_data[$i][4] }}
                    </td>
                    <td>
                        {{ $web_data[$i][5] }}
                    </td>
                    <td>
                        {{ $web_data[$i][6] }}
                    </td>
                </tr>
            @endfor
        </table>
           
    </body>
</html>
