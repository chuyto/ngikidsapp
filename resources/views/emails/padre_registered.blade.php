<!DOCTYPE html>
<html>
<head>
    <title>Credencial de Acceso</title>
</head>
<body>
    <h1>¡Hola, {{ $padre->nombre }}!</h1>
    <p>Te has registrado exitosamente en nuestro sistema.</p>
    <p>Tu credencial de acceso (UUID) es: <strong>{{ $padre->uuid_short }}</strong></p>
    <p>Por favor, utiliza esta credencial para los registros de asistencia de tus hijos.</p>
    <p>Gracias.</p>
</body>
</html>
