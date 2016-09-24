// Model class for each Port item
var PortModel = Backbone.Model.extend({
  defaults: {
    id: null,
    name: null,
    occupation: null
  }/*,
  initialize: function(){
    this.on("change:name", function(model){
      var name = model.get("name"); // 'Stewie Griffin'
      alert("Changed my name to " + name );
    });
  }*/
});



// Collection class for the Ports list endpoint
var PortCollection = Backbone.Collection.extend({
  model: PortModel,
  url: '/ports',

  parse: function(data) {
    return data.ports;
  }
  
});

//alert(JSON.stringify(portsCol));
//alert(PortCollection);

// View class for displaying each port list item
var PortsListItemView = Backbone.View.extend({
  tagName: 'tr',
  className: 'port',
  template: _.template($('#port-item-tmpl').html()),

  initialize: function() {
    this.listenTo(this.model, 'destroy', this.remove);
    this.listenTo(this.model, 'save', this.save);
  },

  render: function() {
    var html = this.template(this.model.toJSON());
    this.$el.html(html);
    return this;
  },

  events: {
    'click .remove': 'onRemove',
    'click .update': 'onSave',
    'click .toggle': 'onToggle' //does this work
  },

  onRemove: function() {
    this.model.destroy();
  },
  onSave: function() {
    var name = this.$('#port-name-' + this.$('.update').data('id'));
    var job = this.$('#port-job-' + this.$('.update').data('id'));
    var state = this.$('#port-state-' + this.$('.update').data('id'));

    if (name.val()) {
      this.model.save({  // save syncs to storage, set just updates the model
        id: this.model.id,
        name: name.val(),
        occupation: job.val(),
        state: state.val()
      });

    }
  },
  onToggle: function() {
    var name = this.$('#port-name-' + this.$('.update').data('id'));
    var job = this.$('#port-job-' + this.$('.update').data('id'));
    var state = this.$('#port-state-' + this.$('.update').data('id'));
    if (state.val() == 0){
      state.val(1);
    } else {
      state.val(0);
    }

    if (name.val()) {
      this.model.save({  // save syncs to storage, set just updates the model
        id: this.model.id,
        name: name.val(),
        occupation: job.val(),
        state: state.val()
      });

    }
  }
});

var PortsListFormView = Backbone.View.extend({
  tagName: 'tr',
  className: 'port',
  template: _.template($('#port-new-tmpl').html()),

  render: function() {
    var html = this.template();
    this.$el.html(html);
    return this;
  }
});

// View class for rendering the list of all ports
var PortsListView = Backbone.View.extend({
  el: '#ports-app',

  initialize: function() {
    this.listenTo(this.collection, 'sync', this.render);
  },

  render: function() {
    var $list = this.$('table.ports-list > tbody').empty();

    this.collection.each(function(model) {
      var item = new PortsListItemView({model: model});
      $list.append(item.render().$el);
    }, this);

    var form = new PortsListFormView();
    $list.append(form.render().$el);
    return this;
  },

  events: {
    'click .create': 'onCreate'
  },

  onCreate: function() {
    var $name = this.$('#port-name');
    var $job = this.$('#port-job');

    if ($name.val()) {
      //alert($name.val());die;
      this.collection.create({
        name: $name.val(),
        occupation: $job.val()
      });

      //$name.val('');
      //$job.val('');
    }
  }
});

// Create a new list collection, a list view, and then fetch list data:
var portsList = new PortCollection();
var portsView = new PortsListView({collection: portsList});
portsList.fetch();