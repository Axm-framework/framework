# Clase FluentInterface

La clase `FluentInterface` es una herramienta para PHP que proporciona una interfaz fluida para encadenar métodos y controlar el flujo de operaciones de manera concisa en aplicaciones PHP. Su propósito principal es mejorar la legibilidad del código, permitiéndote encadenar una serie de métodos de manera coherente y fácil de seguir.Esta es la mayor pontencialidad que tiene esta interfaz, ya no es necesario devolver repetidamente el objeto $this, ahora `FluentInterface` lo gestiona automáticamente por ti.

## Encadenamiento de Métodos

Puedes encadenar varios métodos, lo que facilita la ejecución de múltiples operaciones de manera secuencial. Por ejemplo:

```php
$result = __(new MyClass)
    ->method1()
    ->method2()
    ->method3()
    ->get();
```

## Control de Flujo

La clase permite un control preciso del flujo de ejecución de métodos utilizando condiciones if, elseif y else. Esto es útil para ejecutar operaciones específicas en función de condiciones determinadas. Por ejemplo:

```php
$result = __(User::class)

    ->if(fn ($user) => $user->isAdmin())
    ->setProperty('type', 'admin')
    ->return('You are an admin.')

    ->elseif(fn ($user) => $user->isUser())
    ->setProperty('type', 'user')
    ->return('You are a user.')

    ->else()
    ->setProperty('type', 'unknown')
    ->return('You are not logged in.')
    ->get();
```

## Creación Dinámica de Instancias

Puedes crear instancias de clases dinámicamente y establecerlas como el objeto actual. Esto es útil cuando necesitas trabajar con diferentes objetos de manera flexible. Por ejemplo:

```php
$result = __(new MyClass)  //new instance
->method1()
->method2()
->new('SomeClass')    //resets the previous intent with a new `FluentInterface` class to continue chaining.
->method1()
->method2()
->method3()
->new('OtherClass')    //resets the previous intent with a new `FluentInterface` class to continue chaining.
->method1()
->method2()
->method3()

->get('method1');   //gets the value in this case of the return of method 1 of the last instance
```

## Manejo de Excepciones

La clase FluentInterface maneja excepciones y te permite lanzar excepciones durante la ejecución, lo que facilita la toma de decisiones basadas en condiciones y excepciones arrojadas.

```php
$result = __(new MyClass)
    ->boot()
    ->getErrors()
    ->throwIf(fn ($user) => $fi->get('getError') > 0, , 'Ha ocurrido un error al inicializar.')
```

## Funciones de Depuración

Ofrece métodos como dd(), dump(), y echo() para ayudar en la depuración y el análisis de resultados.

##### Método dd($key = null)

El método dd() (dump and die) se utiliza para depurar y analizar los resultados de la clase FluentInterface. Puedes usarlo para mostrar de manera detallada el contenido de la variable actual. Si proporcionas una clave ($key), se mostrará solo esa entrada específica de los resultados.

Ejemplo de uso:

```php
$result = __(new MyClass)
->incrementar(10)
->duplicate()
->dd();
```

##### Método dump($key = null)

El método dump() se utiliza para depurar y analizar los resultados de la clase FluentInterface. Al igual que dd(), puedes proporcionar una clave ($key) para mostrar solo una entrada específica de los resultados.

Ejemplo de uso:

```php
$result = __(new MyClass)
->incrementar(10)
->duplicate()
->dump('duplicate');       //will return the value of the duplicate method call.
```

##### Método echo($value = null)

El método echo() se utiliza para imprimir los resultados de la clase FluentInterface. Puedes proporcionar un valor ($value) opcional para imprimir algo específico. Si no se proporciona un valor, imprimirá todos los resultados actuales.

Ejemplo de uso:

```php
$result = __(new MyClass)
->incrementar(10)
->duplicate()
->echo();
```

## Uso de Métodos Personalizados

Además de los métodos incorporados, puedes agregar tus propios métodos personalizados utilizando addCustomMethod(). Esto extiende la funcionalidad de la clase según tus necesidades específicas.

```php
$result = __(new MyClass)
->addCustomMethod('call', function ($obj) {
// Define your own logic here
})
->addCustomMethod('getName', function ($obj) {
// Define your own logic here
})
->call()
->getName()

->all();
```

## Interfaz con Colecciones Laravel

La clase puede trabajar con colecciones Laravel y ejecutar métodos de colección en ellas. Solo tienes que pasar un array como argumento de la clase FluentInterface.
Por ejemplo:

```php
$collection = [
    ['name' => 'John Doe',  'age' => 30],
    ['name' => 'Jane Doe',  'age' => 25],
    ['name' => 'John Smith','age' => 40],
];

$result = __($collection)
->filter(function ($item) {
    return $item['age'] > 25;
})
->sort(function ($a, $b) {
    return $a['name'] <=> $b['name'];
});

$result->all();
```

##### Ejemplos de Uso de Fluent Interface con un objeto.

Ejemplo 1: Operaciones en un Valor Numérico
Este ejemplo ilustra el uso de la clase MiClase, que proporciona una interfaz fluida para realizar operaciones en un valor numérico. La clase MiClase tiene tres métodos principales: `incrementar()`, `duplicate()`, y `getValue()`.

```php
    class MiClase
    {
        public $value = 0;

        public function incrementar($cantidad)
        {
            return $this->value += $cantidad;
        }

        public function duplicate()
        {
            return $this->value *= 2;
        }

        public function getValue()
        {
            return $this->value;
        }
    }
```

Implementación de Fluent Interface:

```php
$res = __(MiClase::class)
    ->incrementar(5)
    ->duplicate()
    ->incrementar(5)

    ->if(fn ($fi) => $fi->value > 20)
    ->incrementar(5)

    ->elseif(fn ($fi) => $fi->value < 15)
    ->incrementar(10)

    ->else()
    ->incrementar(10)

    ->getValue()->dd('getValue');

```

Ejemplo 2: Validación de Entrada de Usuario
Este ejemplo muestra cómo validar la entrada de usuario y tomar decisiones basadas en condiciones específicas. La clase FluentInterface proporciona una interfaz fluida para encadenar métodos y controlar el flujo de ejecución de manera efectiva.

```php
__($request->input())   //input array

    ->if(fn ($item) => $item['name'] === '')
    ->throwIf(true, 'The name field is required.')

    ->if(fn ($item) => $item['email'] === '')
    ->throwIf(true, 'The email field is required.');

    ->new(Auth::class)     // Create a new instance of the class 'Auth'.
    ->if(fn ($user) => $user->hasPermission('admin'))
    ->return(['Admin Dashboard', 'User Management','Role Management',])

    ->elseif(fn ($user) => $user->hasPermission('user'))
    ->return(['My Profile','My Orders','My Account',])

    ->else()
    ->return(['Login','Register',])
    ->get('return');
```

Ejemplo 3: Generación de Informes Dinámicos
Imagina que estás desarrollando una aplicación de generación de informes que permite a los usuarios configurar y personalizar informes según sus necesidades. En esta situación, FluentInterface puede simplificar la construcción de informes dinámicos.

Supongamos que tienes una clase ReportBuilder que se utiliza para construir informes. Puedes utilizar FluentInterface para encadenar métodos y configurar dinámicamente los componentes del informe, como encabezados, gráficos, datos y formatos de salida.

```php
// Crear un informe personalizado utilizando FluentInterface.
__(ReportBuilder::class)
    ->setHeader('Informe de Ventas')
    ->setSubtitle('Resultados mensuales')
    ->setChart('Ventas por mes', 'bar')
    ->addData('Enero', 1000)
    ->addData('Febrero', 1200)
    ->addData('Marzo', 800)
    ->setFooter('© 2023 Mi Empresa')
    ->setFormat('PDF')
    ->generateReport();
```

En este ejemplo, FluentInterface permite una configuración fluida del informe. Puedes establecer el encabezado, el subtítulo, agregar datos mensuales, configurar el formato de salida y generar el informe, todo en una secuencia coherente de métodos encadenados.Este enfoque facilita la construcción de informes personalizados de manera programática y permite a los usuarios finales crear informes de manera eficiente según sus necesidades específicas. Además, mejora la legibilidad y mantenibilidad del código relacionado con la generación de informes.

Ejemplo 4: Construcción de Formularios Configurables
Imagina que estás desarrollando una plataforma de creación de formularios en la que los usuarios pueden diseñar sus propios formularios con campos personalizados. FluentInterface puede simplificar la creación y manipulación de formularios dinámicos.

```php
$form = __(FormBuilder::class)
    ->setTitle('Formulario de Contacto')
    ->addField('Nombre', 'text')
    ->addField('Correo Electrónico', 'email')
    ->addField('Mensaje', 'textarea')
    ->addButton('Enviar', 'submit')
    ->setAction('/submit-form')
    ->setMethod('POST')
    ->generateForm();
```

Ejemplo 4: Envío de Correos Electrónicos Personalizados
Supongamos que estás desarrollando una aplicación que envía correos electrónicos personalizados a los usuarios. FluentInterface puede simplificar la construcción de estos correos electrónicos.

```php
$email = __(EmailBuilder::class)
    ->setRecipient('usuario@example.com')
    ->setSubject('¡Bienvenido!')
    ->setBody('Hola, [nombre]. Gracias por unirte a nuestro sitio web.')
    ->addAttachment('factura.pdf')
    ->setSender('info@miempresa.com')
    ->send();

```

Ejemplo 5: Generación de Consultas SQL Dinámicas
Supongamos que estás desarrollando una aplicación web que necesita generar consultas SQL dinámicas para interactuar con una base de datos. Puedes usar FluentInterface para construir estas consultas de manera programática y legible:

```php
$query = __(QueryBuilder::class)
    ->select('nombre', 'email')
    ->from('usuarios')
    ->where('edad', '>', 25)
    ->andWhere('ciudad', '=', 'Nueva York')
    ->orderBy('nombre', 'ASC')
    ->limit(10)
    ->execute();
```

Ejemplo 6: Creación de Gráficos Interactivos
Supongamos que estás desarrollando una aplicación web que muestra gráficos interactivos a los usuarios. FluentInterface puede ayudarte a construir y configurar estos gráficos de manera flexible:

```php
$chart = __(ChartBuilder::class)
    ->setType('line')
    ->setTitle('Ventas Mensuales')
    ->addData('Enero', [100, 150, 200, 120])
    ->addData('Febrero', [120, 160, 180, 140])
    ->setXAxisLabels(['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'])
    ->setYAxisLabel('Ventas (en miles)')
    ->render();
```
