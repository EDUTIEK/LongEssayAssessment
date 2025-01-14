<?php


class ilLongEssayAssessmentDispatchGUI extends ilObjPluginDispatchGUI implements ilCtrlBaseClassInterface
{
    public function executeCommand(): void
    {
        $ilCtrl = $this->ctrl;

        $next_class = $ilCtrl->getNextClass();
        $cmd_class = strtolower($ilCtrl->getCmdClass());
        if (($cmd_class !== "illongessayassessmentdispatchgui" && $cmd_class !== "" && $cmd_class !== null)
            || $next_class === 'ilobjlongessayassessmentgui') {
            $class_path = $ilCtrl->lookupClassPath($next_class);
            // note: $next_class is lower case, $class_name
            // has the correct case so that new $class_name will work
            // also note: if other places did a new $class_name already
            // the lower case name will work here "by accident", too
            $class_name = $ilCtrl->getClassForClasspath($class_path);
            $this->gui_obj = new $class_name($this->request->getRefId());
            $ilCtrl->forwardCommand($this->gui_obj);
        } else {
            $this->processCommand($ilCtrl->getCmd());
        }
    }
}