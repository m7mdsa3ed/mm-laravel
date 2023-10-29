<!doctype html>
<html lang="en" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Demo</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    </head>
    <body class="container">
        <main class="py-3">
            <button class="btn btn-primary" onclick="newRegister()">
                Register New Device
            </button>

            <button class="btn btn-primary" onclick="checkRegistration()">
                Check Registration
            </button>

            <button class="btn btn-primary" onclick="fetchCertificates()">
                Fetch MDS Certificates
            </button>

            <button class="btn btn-primary" onclick="fetchRegistrations()">
                Fetch Registrations
            </button>

            <button class="btn btn-primary" onclick="deleteRegistration()">
                Delete Registration
            </button>

            <hr>

            <div class="d-flex gap-3" id="passKeys">
                @include('passKeys', $passKeys ?? [])
            </div>
        </main>

        <script>
            const getCreateArguments = async () => {
                const response = await fetch('createArguments', {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })

                return response.json()
            }

            const getArguments = async () => {
                const response = await fetch('getArguments', {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })

                return response.json()
            }

            const createNavigatorCredentials = async (createArguments) => {
                return await navigator.credentials.create(createArguments);
            }

            const getNavigatorCredentials = async (navigatorCredentials) => {
                return await navigator.credentials.get(navigatorCredentials);
            }

            const createProcess = async (navigatorCredentials, challenge) => {
                const authenticatorAttestationResponse = {
                    transports: navigatorCredentials?.response?.getTransports ? navigatorCredentials.response.getTransports() : null,
                    clientDataJSON: navigatorCredentials?.response?.clientDataJSON ? arrayBufferToBase64(navigatorCredentials.response.clientDataJSON) : null,
                    attestationObject: navigatorCredentials?.response?.attestationObject ? arrayBufferToBase64(navigatorCredentials.response.attestationObject) : null,
                    challenge,
                }

                const response = await fetch('createProcess', {
                    method: 'POST',
                    body: JSON.stringify(authenticatorAttestationResponse),
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                return await response.json();
            }

            const getProcess = async (navigatorCredentials, challenge) => {
                const authenticatorAttestationResponse = {
                    id: navigatorCredentials?.rawId ? arrayBufferToBase64(navigatorCredentials.rawId) : null,
                    clientDataJSON: navigatorCredentials?.response?.clientDataJSON ? arrayBufferToBase64(navigatorCredentials.response.clientDataJSON) : null,
                    authenticatorData: navigatorCredentials?.response?.authenticatorData ? arrayBufferToBase64(navigatorCredentials.response.authenticatorData) : null,
                    signature: navigatorCredentials?.response?.signature ? arrayBufferToBase64(navigatorCredentials.response.signature) : null,
                    userHandle: navigatorCredentials?.response?.userHandle ? arrayBufferToBase64(navigatorCredentials.response.userHandle) : null,
                    challenge,
                };

                const response = await fetch('getProcess', {
                    method: 'POST',
                    body: JSON.stringify(authenticatorAttestationResponse),
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })

                return response.json()
            }

            const recursiveBase64StrToArrayBuffer = (obj) => {
                let prefix = '=?BINARY?B?';

                let suffix = '?=';

                if (typeof obj === 'object') {
                    for (let key in obj) {
                        if (typeof obj[key] === 'string') {
                            let str = obj[key];

                            if (str.substring(0, prefix.length) === prefix && str.substring(str.length - suffix.length) === suffix) {
                                str = str.substring(prefix.length, str.length - suffix.length);

                                let binary_string = window.atob(str);

                                let len = binary_string.length;

                                let bytes = new Uint8Array(len);

                                for (let i = 0; i < len; i++) {
                                    bytes[i] = binary_string.charCodeAt(i);
                                }

                                obj[key] = bytes.buffer;
                            }
                        } else {
                            recursiveBase64StrToArrayBuffer(obj[key]);
                        }
                    }
                }
            }

            const arrayBufferToBase64 = (buffer) => {
                let binary = '';

                let bytes = new Uint8Array(buffer);

                let len = bytes.byteLength;

                for (let i = 0; i < len; i++) {
                    binary += String.fromCharCode(bytes[i]);
                }

                return window.btoa(binary);
            }

            const newRegister = async () => {
                try {
                    const {arguments, challenge} = await getCreateArguments()

                    recursiveBase64StrToArrayBuffer(arguments);

                    const navigatorCredentials = await createNavigatorCredentials(arguments)

                    await createProcess(navigatorCredentials, challenge)

                    await renderRegistrations();

                    alert('Registration successful')
                } catch(error) {
                    alert(error.message)
                }
            }

            const checkRegistration = async () => {
                try {
                    const {arguments, challenge} = await getArguments()

                    recursiveBase64StrToArrayBuffer(arguments);

                    const navigatorCredentials = await getNavigatorCredentials(arguments)

                    await getProcess(navigatorCredentials, challenge)

                    alert('Registration exists')
                } catch (error) {
                    alert(error.message)
                }
            }

            const fetchCertificates = async () => {
                try {
                    const response = await fetch('refreshCertificates', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        }
                    })

                    const status = await response.json()

                    alert(status.message);
                } catch (error) {
                    alert(error.message)
                }
            }

            const fetchRegistrations = async () => {
                const response = await fetch('fetchRegistrations')

                return response.json()
            }

            const renderRegistrations = async () => {
                try {
                    const {viewContent} = await fetchRegistrations()

                    document.querySelector('#passKeys').innerHTML = viewContent
                } catch (error) {
                    alert(error.message)
                }
            }

            const deleteRegistration = async () => {
                const response = await fetch('deleteRegistration', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    }
                })
            }
        </script>
    </body>
</html>
