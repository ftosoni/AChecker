<?php
include_once(AC_INCLUDE_PATH.'classes/DAO/GuidelineGroupsDAO.class.php');
include_once(AC_INCLUDE_PATH.'classes/DAO/GuidelineSubgroupsDAO.class.php');
include_once(AC_INCLUDE_PATH.'classes/DAO/ChecksDAO.class.php');

$guidelineGroupsDAO = new GuidelineGroupsDAO();
$guidelineSubgroupsDAO = new GuidelineSubgroupsDAO();
$checksDAO = new ChecksDAO();

$num_of_checks = 0;

function dispaly_check_table($checks_array)
{
	if (is_array($checks_array)){ 
?>
	<table class="cdx-table">
		<thead>
		<tr>
			<th><?php echo _AC('html_tag'); ?></th>
			<th><?php echo _AC('error_type'); ?></th>
			<th><?php echo _AC('description'); ?></th>
			<th><?php echo _AC('check_id'); ?></th>
		</tr>
		</thead>
		
		<tbody>
	<?php foreach ($checks_array as $check_row) { ?>
		<tr>
			<td><code><?php echo htmlspecialchars($check_row['html_tag']); ?></code></td>
			<td><?php echo get_confidence_by_code($check_row['confidence']); ?></td>
			<td><a target="_new" href="<?php echo AC_BASE_HREF; ?>checker/suggestion.php?id=<?php echo $check_row["check_id"]; ?>" onclick="AChecker.popup('<?php echo AC_BASE_HREF; ?>checker/suggestion.php?id=<?php echo $check_row["check_id"]; ?>'); return false;"><?php echo htmlspecialchars(_AC($check_row['name'])); ?></a></td>
			<td><?php echo $check_row['check_id']; ?></td>
		</tr>
	<?php } ?>
		</tbody>
	</table>
	<?php }
}

include(AC_INCLUDE_PATH.'header.inc.php');
?>

<div class="cdx-card">
	<h1><?php echo $row["title"]; ?></h1>
	
	<table class="cdx-table cdx-decision-table">
	<?php if ($row["abbr"] <> "") { ?>
		<tr>
			<th><?php echo _AC("abbr"); ?></th>
			<td><?php echo $row["abbr"]; ?></td>
		</tr>
	<?php } ?>
	
	<?php if ($row["long_name"] <> "") { ?>
		<tr>
			<th><?php echo _AC("long_name"); ?></th>
			<td><?php echo _AC($row["long_name"]); ?></td>
		</tr>
	<?php } ?>
			
	<?php if ($row["published_date"] <> "") { ?>
		<tr>
			<th><?php echo _AC("published_date"); ?></th>
			<td><?php echo $row["published_date"]; ?></td>
		</tr>
	<?php } ?>

	<?php if ($row["earlid"] <> "") { ?>
		<tr>
			<th><?php echo _AC("earlid"); ?></th>
			<td><a href="<?php echo $row["earlid"]; ?>"><?php echo $row["earlid"]; ?></a></td>
		</tr>
	<?php } ?>
			
	<?php $status = get_status_by_code($row['status']);
	if ($status <> "") { ?>
		<tr>
			<th><?php echo _AC("status"); ?></th>
			<td><?php echo $status; ?></td>
		</tr>
	<?php } ?>
			
		<tr>
			<th><?php echo _AC("open_to_public"); ?></th>
			<td><?php if ($row['open_to_public']) echo _AC('yes'); else echo _AC('no'); ?></td>
		</tr>
	</table>
	
	<h2 style="margin-top: 40px;"><?php echo _AC('checks'); ?></h2>
<?php 
$guidelineLevel_checks = $checksDAO->getGuidelineLevelChecks($gid);
if (is_array($guidelineLevel_checks))
{
	$num_of_checks += count($guidelineLevel_checks);
	dispaly_check_table($guidelineLevel_checks);
}

$named_groups = $guidelineGroupsDAO->getNamedGroupsByGuidelineID($gid);
if (is_array($named_groups))
{
	foreach ($named_groups as $group)
	{
?>
	<h3 style="margin-top: 30px;"><?php echo _AC($group['name']);?></h3>
<?php
		$groupLevel_checks = $checksDAO->getGroupLevelChecks($group['group_id']);
		if (is_array($groupLevel_checks))
		{
			$num_of_checks += count($groupLevel_checks);
			dispaly_check_table($groupLevel_checks);
		}
		
		$named_subgroups = $guidelineSubgroupsDAO->getNamedSubgroupByGroupID($group['group_id']);
		if (is_array($named_subgroups))
		{
			foreach ($named_subgroups as $subgroup)
			{
?>
	<h4 style="margin-top: 20px;"><?php echo _AC($subgroup['name']);?></h4>
<?php 
				$subgroup_checks = $checksDAO->getChecksBySubgroupID($subgroup['subgroup_id']);
				if (is_array($subgroup_checks))
				{
					$num_of_checks += count($subgroup_checks);
					dispaly_check_table($subgroup_checks);
				}
				else
					echo '<p>'._AC('none_found').'</p>';
			}
		}
	}
}

if ($num_of_checks == 0) echo '<p>'._AC('none_found').'</p>';
?>
</div>

<?php
include(AC_INCLUDE_PATH.'footer.inc.php');
?>
