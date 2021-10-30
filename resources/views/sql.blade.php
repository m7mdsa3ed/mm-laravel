<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/styles/a11y-dark.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <title>Document</title>
</head>
<body class="bg-dark text-light">

    <div class="container-xxl pt-4">
        <textarea rows="1" id="sql" class="form-control bg-dark text-light mb-4"></textarea>

        <button onclick="runSql()" class="btn btn-danger w-100 fw-bold mb-4">
            Run
        </button>

        <span id="time"></span>
        <pre class="rounded-3"><code class="language-json " id="results"></code></pre>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/highlight.min.js"></script>
    <script>

        const runSql = () => {

            const sql = document.querySelector('#sql').value ?? null
            const fd = new FormData

            fd.append('sql', sql)

            axios.post(`{{ route('sql') }}`, fd)
                .then(response => {

                    const { data, time } = response.data

                    document.querySelector('#results').innerHTML = JSON.stringify(data, null, "  ")
                    hljs.highlightBlock(document.querySelector('#results'))

                    document.querySelector('#time').innerHTML = `${time}`
                })
        }

    </script>
</body>
</html>
