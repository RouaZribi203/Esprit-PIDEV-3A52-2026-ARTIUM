# Offcanvas Popup Guide (Add Event Example)

This guide shows how to create a new offcanvas popup using your existing base partial and wire it to a page and button.

## 1) Create a new offcanvas partial

Create a new Twig file:

- templates/Front Office/Partials/offcanvas_event.html.twig

Paste this starter template:

```twig
{% set offcanvas_id = offcanvas_id|default('offcanvasEvent') %}
{% set title = title|default('Ajouter un evenement') %}
{% set subtitle = subtitle|default('Renseignez les details de l evenement') %}
{% set event_form_action = event_form_action|default('#') %}

{% embed 'Front Office/Partials/offcanvas_base.html.twig' with {
	offcanvas_id: offcanvas_id,
	title: title,
	subtitle: subtitle
} %}
	{% block offcanvas_body %}
		<div class="card shadow-sm border-0">
			<div class="card-body">
				<form class="row g-3" action="{{ event_form_action }}" method="post" enctype="multipart/form-data">
					<div class="col-md-6">
						<label class="form-label" for="eventTitle">Titre</label>
						<input class="form-control" type="text" id="eventTitle" name="eventTitle" required>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="eventDate">Date</label>
						<input class="form-control" type="date" id="eventDate" name="eventDate" required>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="eventTime">Heure</label>
						<input class="form-control" type="time" id="eventTime" name="eventTime">
					</div>
					<div class="col-md-6">
						<label class="form-label" for="eventLocation">Lieu</label>
						<input class="form-control" type="text" id="eventLocation" name="eventLocation">
					</div>
					<div class="col-12">
						<label class="form-label" for="eventDescription">Description</label>
						<textarea class="form-control" id="eventDescription" name="eventDescription" rows="4"></textarea>
					</div>
					<div class="col-12 d-flex justify-content-end">
						<button class="btn btn-primary" type="submit">Enregistrer</button>
					</div>
				</form>
			</div>
		</div>
	{% endblock %}
{% endembed %}
```

## 2) Include the popup in a page

In any page template where you want the popup to exist, include it:

```twig
{% include 'Front Office/Partials/offcanvas_event.html.twig' with {
	offcanvas_id: 'offcanvasEvent',
	title: 'Ajouter un evenement',
	subtitle: 'Renseignez les details',
	event_form_action: path('event_create')
} %}
```

## 3) Add a button to open the popup

```twig
<button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasEvent" aria-controls="offcanvasEvent">
	Ajouter un evenement
</button>
```

## 4) Optional: adjust size and stacking

Your base template supports `width` and `z_index` variables. Set them when you include the partial:

```twig
{% include 'Front Office/Partials/offcanvas_event.html.twig' with {
	offcanvas_id: 'offcanvasEvent',
	width: '700px',
	z_index: 2100
} %}
```

## 5) Hook up the backend (basic idea)

Create a Symfony route/controller that receives the form submission:

- Route name example: `event_create`
- Method: POST
- Validate and persist the event
- Redirect or return a response

You can later replace the simple HTML form with a Symfony Form if desired.

## 6) Troubleshooting

- If the popup does not open, check that the button `data-bs-target` matches the `offcanvas_id`.
- If the title/subtitle does not show, verify the variables passed to the include.
- If styles look off, confirm Bootstrap and your CSS are loaded on the page.

---

If you want, I can generate the event partial for you, wire it into a specific page, and add a controller route.
