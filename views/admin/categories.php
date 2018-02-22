<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="admin/">Verwaltung</a></li>
    <li class="breadcrumb-item active" aria-current="page">Kategorien</li>
  </ol>
</nav>

<form method="POST" action="admin/categories/add.php">
	<p class="text-left"><button class="btn btn-primary">Kategorie anlegen</button></p>
</form>

<?php foreach($categories as $category){ ?>
<div class="card mx-auto my-4">
	<div class="card-header d-flex justify-content-between align-items-center">
		<?php echo $category->getName(); ?>
		<div class="row">
			<form class="col px-1" method="POST" action="">
				<button class="btn btn-danger btn-sm del-link" onclick="return confirm('Kategorie <?php echo $category->getName(); ?> wirklich löschen?');">
					<i class="fas fa-times"></i> löschen</button>
				<input type="hidden" name="action" value="del_cat">
				<input type="hidden" name="id" value="<?php echo $category->getId(); ?>">
			</form>
			<form class="col px-1" method="POST" action="admin/categories/edit.php?id=<?php echo $category->getId(); ?>">
				<button class="btn btn-primary btn-sm">
					<i class="fas fa-pencil-alt"></i> bearbeiten</button>
			</form>
		</div>
	</div>
	
	<ul class="list-group list-group-flush m-0">
		<?php if(isset($subcategories[$category->getId()])){ foreach($subcategories[$category->getId()] as $subcategory){ ?>
		<li class="list-group-item d-flex justify-content-between align-items-center">
			<?php echo $subcategory->getName(); ?>
			<div class="row">
    			<form class="col px-1" method="POST" action="">
    				<button class="btn btn-danger btn-sm del-link" onclick="return confirm('Unterkategorie <?php echo $subcategory->getName(); ?> wirklich löschen?');">
    					<i class="fas fa-times"></i> löschen</button>
    				<input type="hidden" name="action" value="del_subcat">
    				<input type="hidden" name="id" value="<?php echo $subcategory->getId(); ?>">
    			</form>
    			<form class="col px-1" method="POST" action="admin/subcategories/edit.php?id=<?php echo $subcategory->getId(); ?>">
    				<button class="btn btn-primary btn-sm">
    					<i class="fas fa-pencil-alt"></i> bearbeiten</button>
    			</form>
			</div>
		</li>
		<?php } } ?>
		<li class="list-group-item">
			<form method="POST" action="admin/subcategories/add.php">
				<input type="hidden" name="category" value="<?php echo $category->getId(); ?>">
        		<button class="btn btn-primary">Unterkategorie anlegen</button>
        	</form>
        </li>
	</ul>

</div>
<?php } ?>