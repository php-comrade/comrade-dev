import {Component, Input, OnInit} from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/do';
import 'rxjs/add/operator/map';
import {HttpService} from "../shared/http.service";
import {Trigger} from "../shared/trigger";
import {GetTriggers} from "../shared/messages/get-triggers";
import {Response} from "@angular/http";

@Component({
  selector: 'template-triggers',
  template: `
      <list-triggers [triggers]="triggers" [showRemove]="false"></list-triggers>
  `
})
export class TemplateTriggersComponent implements OnInit {
  @Input() template: JobTemplate;

  triggers: Trigger[];

  constructor(private httpService: HttpService) {}

  ngOnInit(): void {
    this.httpService.post('/api/get-triggers', new GetTriggers(this.template.templateId))
      .map((res: Response) => res.json().triggers as Trigger[])
      .subscribe((triggers: Trigger[]) => this.triggers = triggers);
  }
}
