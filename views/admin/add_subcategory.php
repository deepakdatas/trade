<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="admin/">Verwaltung</a></li>
    <li class="breadcrumb-item"><a href="admin/categories.php">Kategorien</a></li>
    <li class="breadcrumb-item"><a href="admin/categories.php"><?php echo $category->getName(); ?></a>
    <li class="breadcrumb-item active" aria-current="page">Neue Unterkategorie</li>
  </ol>
</nav>

<h1>Unterategorie anlegen</h1>

<form class="form-inline" method="POST" action="">
	<input class="form-control m-2" type="text" name="name" pattern="[A-Za-z0-9äÄöÖüÜß _\-]+">
	<input class="btn btn-primary" type="submit" name="addCategory" value="anlegen">
	<input type="hidden" name="category" value="<?php echo $category->getId(); ?>">
</form>
<small>Erlaubt sind Buchstaben, Zahlen sowie Leerzeichen, "-" und "_"</small>