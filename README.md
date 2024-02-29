<!-- markdownlint-disable no-inline-html -->
<p align="center">
  <br><br>
  <img src="https://github.com/Axm-framework/axm/blob/main/public/axm.png" height="120" alt="Logo"/>
  <br>
</p>

<p align="center">
 <a href="https://packagist.org/packages/axm/axm"
  ><img
   src="https://poser.pugx.org/axm/axm/v/stable"
   alt="Latest Stable Version"
 /></a>
 <a href="https://packagist.org/packages/axm/axm"
  ><img
   src="https://poser.pugx.org/axm/axm/downloads"
   alt="Total Downloads"
 /></a>
 <a href="https://packagist.org/packages/axm/axm"
  ><img
   src="https://poser.pugx.org/axm/axm/license"
   alt="License"
 /></a>
</p>

<br>

## ⚡️ In bed, size doesn't matter; web, speed does

Say goodbye to complex and slow frameworks and embrace the future of web development with Axm! This modern PHP framework offers a clean, simple and powerful solution. With its intuitive structure, Axm provides flexibility without sacrificing simplicity. Its gentle learning curve allows you to quickly create exceptional web applications and APIs. Discover the transformative power of Axm today.

## 😎 Basic Usage

This is a "hello world" application created using Axm. After [installing](#-installation) Axm.

```php
<?php

use Axm\Http\Router as Route;

Route::get('/', function () {
  echo '<h1>Hola Mundo</h1>';
});

Route::get('/home', App\Controllers\HomeController::class);

```

You may quickly test this using the Axm CLI:

```bash
php axm serve
```

Or with the built-in PHP server:

```bash
php -S localhost:8000
```

## ❤️ Why Axm?

Axm is a highly efficient and feature-rich framework that excels at providing a solid foundation for building powerful web applications and APIs. It offers a comprehensive set of tools and features that streamline the development process and enable developers to create high-performance, scalable and maintainable solutions.

One of the main advantages of Axm is its simplicity and ease of use. The framework follows a minimalist approach, focusing on clean and intuitive code syntax. This simplicity not only improves developer productivity, but also enhances code readability and maintainability.

### The problems

+ Although PHP frameworks can improve the efficiency of web development, it is crucial to recognize the challenges and limitations they present. Here are some additional problems commonly associated with contemporary PHP frameworks:

1. **Steep learning curve:** PHP frameworks often carry a demanding learning curve, especially for developers who are unfamiliar with the conventions of the framework or the language itself. Becoming proficient in the intricate concepts and functionality of a framework takes time and effort.

2. **Performance overhead:** Certain PHP frameworks introduce unnecessary performance overhead due to the layers of abstraction and additional feature sets they provide. This can affect the overall execution speed of the application, especially in scenarios where high performance is crucial.

3. **Complexity of code maintenance:** Frameworks often impose specific coding standards and conventions, forcing adherence to established practices. For developers who are not accustomed to these standards, maintaining and updating the code base becomes more complicated and time-consuming.

4. **Limited flexibility:** PHP frameworks often impose a certain level of rigidity, limiting developers in terms of code structure and handling of specific use cases. Predefined architecture and conventions do not always align with unique project requirements, resulting in reduced flexibility and potentially cumbersome solutions.

5. **Excess code and packages:** Many PHP frameworks come with extensive code bases, classes and packages, leading to the inclusion of unnecessary complexity in applications. This additional baggage can lead to bloated code bases and negatively affect performance.

Understanding these potential challenges associated with PHP frameworks is essential when considering their adoption. Developers should carefully evaluate their project requirements and weigh the benefits against the drawbacks to make an informed decision.

### How Axm tackles these

+ Axm excels at solving common problems found in PHP frameworks through a number of technical features and specific approaches:

1. **Ease of learning:** Axm is designed to be the most accessible and easy-to-learn framework. Even developers new to PHP can start creating powerful applications in a matter of minutes by reading the documentation or following tutorials. Axm requires only basic knowledge of PHP.

2. **Lightweight:** Axm stands out as one of the lightest frameworks available. Its optimized and efficient architecture makes it fast and agile compared to other frameworks. Axm minimizes resource usage and offers exceptional performance, resulting in very fast web applications.

3. **High speed:** The combination of its lightness and optimization makes Axm extremely fast. Its optimized code and efficient structure enable fast execution of applications, which improves user experience and optimizes response times.

4. **High productivity:** Axm is designed to maximize developer productivity. It offers a wide range of features and tools that simplify and streamline the development process. From global functions that allow access to classes from anywhere in the application to intuitive modules and features, Axm allows developers to focus on building their applications without having to deal with the time-consuming and costly tasks of creating and deploying them.

5. **Compatibility with libraries:** Axm has been designed to be compatible with several existing libraries and frameworks in the PHP ecosystem, allowing developers to take advantage of different libraries and use them together with Axm to build more complete and powerful applications, making it easier to collaborate and extend functionalities.

6. **Scalability:** Axm is highly scalable and can be adapted to projects of any size. Its flexible and modular architecture allows applications to scale efficiently, either to handle a sudden increase in workload or to add new functionality as the project grows.Axm guarantees optimal performance and seamless scalability to adapt to the changing needs of web applications.

## 📦 Installation

You can also use [Composer](https://getcomposer.org/) to install Axm in your project quickly.

```bash
composer create-project axm/axm
```

## 📢 Stay In Touch

- [Twitter](https://twitter.com/axmphp)
- [Join the forum](https://github.com/axmphp/axm/discussions/)
- [Chat on discord](https://discord.gg/6WgT5whv)

## 📚 Learning Axm

- Axm has a very easy to understand [documentation](https://axmphp.com) which contains information on all operations in Axm.
- You can also check out our [youtube channel](https://www.youtube.com/channel/123w) which has video tutorials on different topics
- You can also learn from [codelabs](https://codelabs.axmphp.dev) and contribute as well.

## 🤝 Contributing

We warmly welcome you to join us in making a difference. Your contributions are highly valued and greatly appreciated! To get started, take a moment to explore our [contribution guide](https://discord.gg/6WgT5whv) and you'll be empowered to launch your first pull request. Together, let's create something extraordinary!

To report a security vulnerability, you can reach out to [@juancristobal_g](https://twitter.com/juancristobal_g) or [@axmphp](https://twitter.com/axmphp) on twitter. We will coordinate the fix and eventually commit the solution in this project.

## 🚀 Sponsoring Axm

Your cash contributions go a long way to help us make Axm even better for you. You can sponsor Axm and any of our packages on [open collective](https://opencollective.com/Axm) or check the [contribution page](https://axmphp.com/support/) for a list of ways to contribute.

## 📝 License

[MIT](https://github.com/Axm-framework/axm/blob/main/LICENSE)
